<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Closure;
use Exception;
use Soap\Encoding\TypeInference\XsiTypeDetector;
use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Reader\DocumentToLookupArrayReader;
use Soap\Encoding\Xml\Writer\AttributeBuilder;
use Soap\Encoding\Xml\Writer\NilAttributeBuilder;
use Soap\Encoding\Xml\Writer\XsdTypeXmlElementWriter;
use Soap\Encoding\Xml\Writer\XsiAttributeBuilder;
use Soap\Engine\Metadata\Model\Property;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Reflecta\Lens\Lens;
use function is_array;
use function Psl\Dict\map_with_key;
use function VeeWee\Reflecta\Iso\object_data;
use function VeeWee\Xml\Writer\Builder\children as writeChildren;
use function VeeWee\Xml\Writer\Builder\raw;
use function VeeWee\Xml\Writer\Builder\value as buildValue;

/**
 * @template TObj extends object
 *
 * @implements XmlEncoder<TObj|array, non-empty-string>
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
     * @return Iso<TObj|array, non-empty-string>
     */
    public function iso(Context $context): Iso
    {
        $objectAccess = ObjectAccess::forContext($context);

        return new Iso(
            /**
             * @param TObj|array $value
             * @return non-empty-string
             */
            function (object|array $value) use ($context, $objectAccess) : string {
                return $this->to($context, $objectAccess, $value);
            },
            /**
             * @param non-empty-string|Element $value
             * @return TObj
             */
            function (string|Element $value) use ($context, $objectAccess) : object {
                return $this->from(
                    $context,
                    $objectAccess,
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
        if (is_array($data)) {
            $data = (object) $data;
        }
        $defaultAction = writeChildren([]);

        return (new XsdTypeXmlElementWriter())(
            $context,
            writeChildren(
                [
                    (new XsiAttributeBuilder(
                        $context,
                        XsiTypeDetector::detectFromValue($context, []),
                        includeXsiTargetNamespace: !$objectAccess->isAnyPropertyQualified,
                    )),
                    ...map_with_key(
                        $objectAccess->properties,
                        static function (string $normalizePropertyName, Property $property) use ($objectAccess, $data, $defaultAction) : Closure {
                            $type = $property->getType();
                            $meta = $type->getMeta();
                            $isAttribute = $meta->isAttribute()->unwrapOr(false);

                            /** @var mixed $value */
                            $value = self::runLens(
                                $objectAccess->encoderLenses[$normalizePropertyName],
                                $data
                            );
                            $iso = $objectAccess->isos[$normalizePropertyName];

                            return match(true) {
                                $isAttribute => $value ? (new AttributeBuilder(
                                    $type,
                                    $iso->to($value)
                                ))(...) : $defaultAction,
                                $property->getName() === '_' => $value !== null
                                    ? buildValue($iso->to($value))
                                    : (new NilAttributeBuilder())(...),
                                default => raw($iso->to($value))
                            };
                        }
                    )
                ]
            )
        );
    }

    /**
     * @return TObj
     */
    private function from(Context $context, ObjectAccess $objectAccess, Element $data): object
    {
        $nodes = (new DocumentToLookupArrayReader())($data);
        /** @var Iso<TObj, array<string, mixed>> $objectData */
        $objectData = object_data($this->className);

        return $objectData->from(
            map_with_key(
                $objectAccess->properties,
                static function (string $normalizePropertyName, Property $property) use ($objectAccess, $nodes): mixed {
                    $type = $property->getType();
                    $meta = $type->getMeta();

                    /** @var string|null $value */
                    $value = self::runLens(
                        $objectAccess->decoderLenses[$normalizePropertyName],
                        $nodes,
                    );
                    $iso = $objectAccess->isos[$normalizePropertyName];
                    $defaultValue = $meta->isList()->unwrapOr(false) ? [] : null;

                    /** @psalm-suppress PossiblyNullArgument */
                    return match(true) {
                        $meta->isAttribute()->unwrapOr(false) => $iso->from($value),
                        default => $value !== null ? $iso->from($value) : $defaultValue,
                    };
                },
            )
        );
    }

    private static function runLens(Lens $lens, mixed $data, mixed $default = null): mixed
    {
        try {
            /** @var mixed */
            return $lens->get($data);
        } catch (Exception $e) {
            return $default;
        }
    }
}
