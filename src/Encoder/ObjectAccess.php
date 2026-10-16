<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\Cache\ScopedCache;
use Soap\Encoding\EncoderRegistry;
use Soap\Encoding\Normalizer\PhpPropertyNameNormalizer;
use Soap\Encoding\TypeInference\ComplexTypeBuilder;
use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Node\ElementList;
use Soap\Engine\Metadata\Model\Property;
use Soap\Engine\Metadata\Model\Type;
use Soap\Engine\Metadata\Model\TypeMeta;
use VeeWee\Reflecta\Iso\IsoInterface;
use VeeWee\Reflecta\Lens\LensInterface;
use function Psl\Vec\sort_by;
use function VeeWee\Reflecta\Lens\optional;
use function VeeWee\Reflecta\Lens\optional_index;
use function VeeWee\Reflecta\Lens\optional_property;

final class ObjectAccess
{
    /**
     * Everything the object encoder needs per property of one type, worked out once and cached per type.
     * Every array is keyed by the normalized PHP property name.
     *
     * @param array<string, Property> $properties The metadata properties, attributes first.
     * @param array<string, LensInterface<object|null, object|null, mixed, mixed>> $encoderLenses Reads the property value from the PHP object on encode.
     * @param array<string, LensInterface<array<array-key, mixed>|null, array<array-key, mixed>|null, mixed, mixed>> $decoderLenses Reads the raw XML value from the lookup array of the element on decode.
     * @param array<string, IsoInterface<mixed, mixed, string|null, Element|ElementList|string|null>> $isos Converts between the PHP value and its XML form.
     * @param bool $isAnyPropertyQualified Whether at least one property is namespace qualified.
     * @param array<string, bool> $isAttribute Whether the property is an XML attribute instead of a child element.
     * @param array<string, list<never>|null> $decodeDefaultValues The value a property decodes to when its element is missing from the XML.
     * @param array<string, bool> $omitWhenAbsent Whether encoding leaves the element out, instead of writing xsi:nil, when the property is absent from the PHP value.
     * @param array<string, bool> $nullWritesNothing Whether a null value encodes to an empty string for this element, so encoding can skip its iso.
     */
    public function __construct(
        public readonly array $properties,
        public readonly array $encoderLenses,
        public readonly array $decoderLenses,
        public readonly array $isos,
        public readonly bool  $isAnyPropertyQualified,
        public readonly array $isAttribute,
        public readonly array $decodeDefaultValues,
        public readonly array $omitWhenAbsent,
        public readonly array $nullWritesNothing,
    ) {
    }

    /**
     * @return ScopedCache<EncoderRegistry, self>
     *
     * @psalm-suppress LessSpecificReturnStatement, MoreSpecificReturnType, MixedReturnStatement
     */
    private static function cache(): ScopedCache
    {
        static $cache = new ScopedCache();

        return $cache;
    }

    private static function cacheKey(Context $context): string
    {
        return $context->type->getXmlNamespace() . '|' . $context->type->getName()
            . '|' . $context->bindingUse->value;
    }

    public static function forContext(Context $context): self
    {
        return self::cache()->lookup(
            $context->registry,
            self::cacheKey($context),
            static fn (): self => self::build($context)
        );
    }

    private static function build(Context $context): self
    {
        $type = ComplexTypeBuilder::default()($context);

        $sortedProperties = sort_by(
            $type->getProperties(),
            static fn (Property $property): bool => !$property->getType()->getMeta()->isAttribute()->unwrapOr(false),
        );

        $normalizedProperties = [];
        $encoderLenses = [];
        $decoderLenses = [];
        $isos = [];
        $isAttribute = [];
        $decodeDefaultValues = [];
        $omitWhenAbsent = [];
        $nullWritesNothing = [];
        $isAnyPropertyQualified = false;

        foreach ($sortedProperties as $property) {
            $propertyType = $property->getType();
            $propertyTypeMeta = $propertyType->getMeta();
            $propertyContext = $context->withType($propertyType);
            $name = $property->getName();
            $normalizedName = PhpPropertyNameNormalizer::normalize($name);

            $encoder = $context->registry->detectEncoderForContext($propertyContext);
            $shouldLensBeOptional = self::shouldLensBeOptional($propertyTypeMeta);
            $normalizedProperties[$normalizedName] = $property;

            $customEncoderLens = self::createCustomEncoderLens($shouldLensBeOptional, $encoder, $type, $property);
            $encoderLenses[$normalizedName] = $customEncoderLens ?? optional_property($normalizedName);
            $decoderLenses[$normalizedName] = self::createCustomDecoderLens($shouldLensBeOptional, $encoder, $type, $property)
                ?? optional_index($name);
            $isos[$normalizedName] = $encoder->iso($propertyContext);
            $isAttribute[$normalizedName] = $propertyTypeMeta->isAttribute()->unwrapOr(false);
            $decodeDefaultValues[$normalizedName] = $propertyTypeMeta->isList()->unwrapOr(false) ? [] : null;
            $omitWhenAbsent[$normalizedName] = $customEncoderLens === null && self::mayBeAbsent($name, $propertyTypeMeta);
            $nullWritesNothing[$normalizedName] = self::nullWritesNothing($name, $propertyTypeMeta, $encoder, $propertyContext);

            $isAnyPropertyQualified = $isAnyPropertyQualified || $propertyTypeMeta->isQualified()->unwrapOr(false);
        }

        return new self(
            $normalizedProperties,
            $encoderLenses,
            $decoderLenses,
            $isos,
            $isAnyPropertyQualified,
            $isAttribute,
            $decodeDefaultValues,
            $omitWhenAbsent,
            $nullWritesNothing,
        );
    }

    /**
     * The encoder decides whether null encodes to nothing; one that does not say so keeps its own null handling.
     */
    private static function nullWritesNothing(string $propertyName, TypeMeta $meta, XmlEncoder $encoder, Context $context): bool
    {
        return $propertyName !== '_'
            && !$meta->isAttribute()->unwrapOr(false)
            && $encoder instanceof Feature\WritesNothingForNull
            && $encoder->writesNothingForNull($context);
    }

    /**
     * Null and absent only encode differently for an optional nillable element: null writes xsi:nil, absent writes nothing.
     * Every other element, attribute and the '_' text value encodes null and absent the same way.
     */
    private static function mayBeAbsent(string $propertyName, TypeMeta $meta): bool
    {
        return $propertyName !== '_'
            && !$meta->isAttribute()->unwrapOr(false)
            && $meta->isNil()->unwrapOr(false)
            && $meta->minOccurs()->unwrapOr(1) === 0;
    }

    /**
     * @return LensInterface<object|null, object|null, mixed, mixed>|null
     */
    private static function createCustomEncoderLens(
        bool $shouldLensBeOptional,
        XmlEncoder $encoder,
        Type $type,
        Property $property,
    ): ?LensInterface {
        $lens = match (true) {
            $encoder instanceof Feature\DecoratingEncoder => self::createCustomEncoderLens($shouldLensBeOptional, $encoder->decoratedEncoder(), $type, $property),
            $encoder instanceof Feature\ProvidesObjectEncoderLens => $encoder::createObjectEncoderLens($type, $property),
            default => null,
        };

        if ($lens === null) {
            return null;
        }

        return $shouldLensBeOptional ? optional($lens) : $lens;
    }

    /**
     * @return LensInterface<array<array-key, mixed>|null, array<array-key, mixed>|null, mixed, mixed>|null
     */
    private static function createCustomDecoderLens(
        bool $shouldLensBeOptional,
        XmlEncoder $encoder,
        Type $type,
        Property $property,
    ): ?LensInterface {
        $lens = match(true) {
            $encoder instanceof Feature\DecoratingEncoder => self::createCustomDecoderLens($shouldLensBeOptional, $encoder->decoratedEncoder(), $type, $property),
            $encoder instanceof Feature\ProvidesObjectDecoderLens => $encoder::createObjectDecoderLens($type, $property),
            default => null,
        };

        if ($lens === null) {
            return null;
        }

        return $shouldLensBeOptional ? optional($lens) : $lens;
    }

    private static function shouldLensBeOptional(TypeMeta $meta): bool
    {
        if ($meta->isNullable()->unwrapOr(false)) {
            return true;
        }

        if (
            $meta->isAttribute()->unwrapOr(false) &&
            $meta->use()->unwrapOr('optional') === 'optional'
        ) {
            return true;
        }

        return false;
    }
}
