<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\Normalizer\PhpPropertyNameNormalizer;
use Soap\Encoding\TypeInference\ComplexTypeBuilder;
use Soap\Engine\Metadata\Model\Property;
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
            $typeMeta = $property->getType()->getMeta();
            $name = $property->getName();
            $normalizedName = PhpPropertyNameNormalizer::normalize($name);

            $shouldLensBeOptional = self::shouldLensBeOptional($typeMeta);
            $normalizedProperties[$normalizedName] = $property;
            $encoderLenses[$normalizedName] = $shouldLensBeOptional ? optional(property($normalizedName)) : property($normalizedName);
            $decoderLenses[$normalizedName] = $shouldLensBeOptional ? optional(index($name)) : index($name);
            $isos[$normalizedName] = self::grabIsoForProperty($context, $property);

            $isAnyPropertyQualified = $isAnyPropertyQualified || $typeMeta->isQualified()->unwrapOr(false);
        }

        return new self(
            $normalizedProperties,
            $encoderLenses,
            $decoderLenses,
            $isos,
            $isAnyPropertyQualified
        );
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

    /**
     * @return Iso<mixed, string>
     */
    private static function grabIsoForProperty(Context $context, Property $property): Iso
    {
        $propertyContext = $context->withType($property->getType());

        return $context->registry->detectEncoderForContext($propertyContext)
            ->iso($propertyContext);
    }
}
