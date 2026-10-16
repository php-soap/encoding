<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Node\ElementList;
use Soap\Encoding\Xml\Node\ElementLookup;
use Soap\Encoding\Xml\Writer\AttributeBuilder;
use Soap\Encoding\Xml\Writer\NilAttributeBuilder;
use Soap\Encoding\Xml\Writer\XsdTypeXmlElementWriter;
use Soap\Encoding\Xml\Writer\XsiAttributeBuilder;
use VeeWee\Reflecta\Iso\Iso;
use function is_array;
use function Psl\Fun\lazy;
use function VeeWee\Reflecta\Iso\object_data;
use function VeeWee\Reflecta\Reflect\property_is_initialized;
use function VeeWee\Xml\Writer\Builder\children as writeChildren;
use function VeeWee\Xml\Writer\Builder\raw;
use function VeeWee\Xml\Writer\Builder\value as buildValue;

/**
 * @template TObj extends object
 *
 * @implements XmlEncoder<TObj|array, TObj, non-empty-string, Element|non-empty-string>
 */
final class ObjectEncoder implements Feature\ElementAware, XmlEncoder
{
    /**
     * @param class-string<TObj> $className
     */
    public function __construct(
        private readonly string $className
    ) {
    }

    /**
     * @return Iso<TObj|array, TObj, non-empty-string, Element|non-empty-string>
     */
    public function iso(Context $context): Iso
    {
        $objectAccess = lazy(static fn (): ObjectAccess => ObjectAccess::forContext($context));

        return new Iso(
            /**
             * @param TObj|array $value
             * @return non-empty-string
             */
            function (object|array $value) use ($context, $objectAccess) : string {
                return $this->to($context, $objectAccess(), $value);
            },
            /**
             * @param non-empty-string|Element $value
             * @return TObj
             */
            function (string|Element $value) use ($context, $objectAccess) : object {
                return $this->from(
                    $context,
                    $objectAccess(),
                    ($value instanceof Element ? $value : Element::fromString($value))
                );
            }
        );
    }

    /**
     * @param TObj|array $data
     *
     * @return non-empty-string
     */
    private function to(Context $context, ObjectAccess $objectAccess, object|array $data): string
    {
        /** @var object $data */
        $data = is_array($data) ? (object) $data : $data;
        $defaultAction = writeChildren([]);

        $children = [
            XsiAttributeBuilder::forEncodedValue(
                $context,
                $this,
                $data,
                forceIncludeXsiTargetNamespace: !$objectAccess->isAnyPropertyQualified,
            ),
        ];
        foreach ($objectAccess->properties as $normalizePropertyName => $property) {
            /** @var mixed $value */
            $value = $objectAccess->encodeReaders[$normalizePropertyName]($data);
            if ($value === null
                && $objectAccess->omitWhenAbsent[$normalizePropertyName]
                && !property_is_initialized($data, $normalizePropertyName)
            ) {
                continue;
            }

            $iso = $objectAccess->isos[$normalizePropertyName];

            $children[] = match(true) {
                $objectAccess->isAttribute[$normalizePropertyName] => $value !== null ? (new AttributeBuilder(
                    $property->getType(),
                    (string) $iso->to($value)
                ))(...) : $defaultAction,
                $property->getName() === '_' => $value !== null
                    ? buildValue((string) $iso->to($value))
                    : (new NilAttributeBuilder())(...),
                default => raw((string) $iso->to($value))
            };
        }

        return (new XsdTypeXmlElementWriter())(
            $context,
            writeChildren($children)
        );
    }

    /**
     * @return TObj
     */
    private function from(Context $context, ObjectAccess $objectAccess, Element $data): object
    {
        $lookup = ElementLookup::fromElement($data);
        /** @var Iso<TObj, TObj, array<string, mixed>, array<string, mixed>> $objectData */
        $objectData = object_data($this->className);

        $result = [];
        foreach ($objectAccess->isos as $normalizePropertyName => $iso) {
            /** @var Element|ElementList|string|null $value */
            $value = $objectAccess->decodeReaders[$normalizePropertyName]($lookup);

            /**
             * @var mixed
             * @psalm-suppress PossiblyNullArgument
             */
            $result[$normalizePropertyName] = match(true) {
                $objectAccess->isAttribute[$normalizePropertyName] => $iso->from($value),
                $value !== null => $iso->from($value),
                default => $objectAccess->decodeDefaultValues[$normalizePropertyName],
            };
        }

        return $objectData->from($result);
    }
}
