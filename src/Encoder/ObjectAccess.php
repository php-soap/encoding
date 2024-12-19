<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\Normalizer\PhpPropertyNameNormalizer;
use Soap\Encoding\TypeInference\ComplexTypeBuilder;
use Soap\Engine\Metadata\Model\Property;
use Soap\Engine\Metadata\Model\Type;
use Soap\Engine\Metadata\Model\TypeMeta;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Reflecta\Lens\Lens;
use function Psl\Vec\sort_by;
use function VeeWee\Reflecta\Lens\index;
use function VeeWee\Reflecta\Lens\optional;
use function VeeWee\Reflecta\Lens\property;

final class ObjectAccess
{
    /**
     * @param array<string, Property> $properties
     * @param array<string, Lens<object, mixed>> $encoderLenses
     * @param array<string, Lens<array, mixed>> $decoderLenses
     * @param array<string, Iso<mixed, string>> $isos
     */
    public function __construct(
        public readonly array $properties,
        public readonly array $encoderLenses,
        public readonly array $decoderLenses,
        public readonly array $isos,
        public readonly bool  $isAnyPropertyQualified
    ) {
    }

    public static function forContext(Context $context): self
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
     * @return Lens<object, mixed>
     */
    private static function createEncoderLensForType(
        bool $shouldLensBeOptional,
        string $normalizedName,
        XmlEncoder $encoder,
        Type $type,
        Property $property,
    ): Lens {
        $lens = match (true) {
            $encoder instanceof Feature\DecoratingEncoder => self::createEncoderLensForType($shouldLensBeOptional, $normalizedName, $encoder->decoratedEncoder(), $type, $property),
            $encoder instanceof Feature\ProvidesObjectEncoderLens => $encoder::createObjectEncoderLens($type, $property),
            default => property($normalizedName)
        };

        /** @var Lens<object, mixed> */
        return $shouldLensBeOptional ? optional($lens) : $lens;
    }

    /**
     * @return Lens<array, mixed>
     */
    private static function createDecoderLensForType(
        bool $shouldLensBeOptional,
        string $name,
        XmlEncoder $encoder,
        Type $type,
        Property $property,
    ): Lens {
        $lens = match(true) {
            $encoder instanceof Feature\DecoratingEncoder => self::createDecoderLensForType($shouldLensBeOptional, $name, $encoder->decoratedEncoder(), $type, $property),
            $encoder instanceof Feature\ProvidesObjectDecoderLens => $encoder::createObjectDecoderLens($type, $property),
            default => index($name),
        };

        /** @var Lens<array, mixed> */
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
