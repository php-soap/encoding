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
     * @param array<string, Property> $properties
     * @param array<string, LensInterface<object|null, object|null, mixed, mixed>> $encoderLenses
     * @param array<string, LensInterface<array<array-key, mixed>|null, array<array-key, mixed>|null, mixed, mixed>> $decoderLenses
     * @param array<string, IsoInterface<mixed, mixed, string|null, Element|ElementList|string|null>> $isos
     * @param array<string, bool> $isAttribute
     * @param array<string, list<never>|null> $decodeDefaultValues
     */
    public function __construct(
        public readonly array $properties,
        public readonly array $encoderLenses,
        public readonly array $decoderLenses,
        public readonly array $isos,
        public readonly bool  $isAnyPropertyQualified,
        public readonly array $isAttribute,
        public readonly array $decodeDefaultValues,
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

            $encoderLenses[$normalizedName] = self::createCustomEncoderLens($shouldLensBeOptional, $encoder, $type, $property)
                ?? optional_property($normalizedName);
            $decoderLenses[$normalizedName] = self::createCustomDecoderLens($shouldLensBeOptional, $encoder, $type, $property)
                ?? optional_index($name);
            $isos[$normalizedName] = $encoder->iso($propertyContext);
            $isAttribute[$normalizedName] = $propertyTypeMeta->isAttribute()->unwrapOr(false);
            $decodeDefaultValues[$normalizedName] = $propertyTypeMeta->isList()->unwrapOr(false) ? [] : null;

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
        );
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
