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
use function VeeWee\Reflecta\Lens\index;
use function VeeWee\Reflecta\Lens\optional;
use function VeeWee\Reflecta\Lens\property;

final class ObjectAccess
{
    /**
     * @param array<string, Property> $properties
     * @param array<string, LensInterface<object|null, object|null, mixed, mixed>> $encoderLenses
     * @param array<string, LensInterface<array<array-key, mixed>|null, array<array-key, mixed>|null, mixed, mixed>> $decoderLenses
     * @param array<string, IsoInterface<mixed, mixed, string|null, Element|ElementList|string|null>> $isos
     */
    public function __construct(
        public readonly array $properties,
        public readonly array $encoderLenses,
        public readonly array $decoderLenses,
        public readonly array $isos,
        public readonly bool  $isAnyPropertyQualified
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

            $encoderLenses[$normalizedName] = self::createEncoderLensForType($shouldLensBeOptional, $normalizedName, $encoder, $type, $property);
            $decoderLenses[$normalizedName] = self::createDecoderLensForType($shouldLensBeOptional, $name, $encoder, $type, $property);
            $isos[$normalizedName] = $encoder->iso($propertyContext);

            $isAnyPropertyQualified = $isAnyPropertyQualified || $propertyTypeMeta->isQualified()->unwrapOr(false);
        }

        return new self(
            $normalizedProperties,
            $encoderLenses,
            $decoderLenses,
            $isos,
            $isAnyPropertyQualified
        );
    }

    /**
     * @return LensInterface<object|null, object|null, mixed, mixed>
     */
    private static function createEncoderLensForType(
        bool $shouldLensBeOptional,
        string $normalizedName,
        XmlEncoder $encoder,
        Type $type,
        Property $property,
    ): LensInterface {
        $lens = match (true) {
            $encoder instanceof Feature\DecoratingEncoder => self::createEncoderLensForType($shouldLensBeOptional, $normalizedName, $encoder->decoratedEncoder(), $type, $property),
            $encoder instanceof Feature\ProvidesObjectEncoderLens => $encoder::createObjectEncoderLens($type, $property),
            default => property($normalizedName)
        };

        return $shouldLensBeOptional ? optional($lens) : $lens;
    }

    /**
     * @return LensInterface<array<array-key, mixed>|null, array<array-key, mixed>|null, mixed, mixed>
     */
    private static function createDecoderLensForType(
        bool $shouldLensBeOptional,
        string $name,
        XmlEncoder $encoder,
        Type $type,
        Property $property,
    ): LensInterface {
        $lens = match(true) {
            $encoder instanceof Feature\DecoratingEncoder => self::createDecoderLensForType($shouldLensBeOptional, $name, $encoder->decoratedEncoder(), $type, $property),
            $encoder instanceof Feature\ProvidesObjectDecoderLens => $encoder::createObjectDecoderLens($type, $property),
            default => index($name),
        };

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
