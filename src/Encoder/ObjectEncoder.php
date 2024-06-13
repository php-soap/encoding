<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Closure;
use Exception;
use Soap\Encoding\Normalizer\PhpPropertyNameNormalizer;
use Soap\Encoding\TypeInference\ComplexTypeBuilder;
use Soap\Encoding\TypeInference\XsiTypeDetector;
use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Reader\DocumentToLookupArrayReader;
use Soap\Encoding\Xml\Writer\AttributeBuilder;
use Soap\Encoding\Xml\Writer\NilAttributeBuilder;
use Soap\Encoding\Xml\Writer\XsdTypeXmlElementWriter;
use Soap\Encoding\Xml\Writer\XsiAttributeBuilder;
use Soap\Engine\Metadata\Model\Property;
use Soap\Engine\Metadata\Model\TypeMeta;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Reflecta\Lens\Lens;
use function is_array;
use function Psl\Dict\map;
use function Psl\Dict\pull;
use function Psl\Dict\reindex;
use function Psl\Iter\any;
use function Psl\Vec\sort_by;
use function VeeWee\Reflecta\Iso\object_data;
use function VeeWee\Reflecta\Lens\index;
use function VeeWee\Reflecta\Lens\optional;
use function VeeWee\Reflecta\Lens\property;
use function VeeWee\Xml\Writer\Builder\children as writeChildren;
use function VeeWee\Xml\Writer\Builder\raw;
use function VeeWee\Xml\Writer\Builder\value as buildValue;

/**
 * @template TObj extends object
 *
 * @implements XmlEncoder<TObj|array, non-empty-string>
 */
final class ObjectEncoder implements XmlEncoder
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
        $properties = $this->detectProperties($context);

        return new Iso(
            /**
             * @param TObj|array $value
             * @return non-empty-string
             */
            function (object|array $value) use ($context, $properties) : string {
                return $this->to($context, $properties, $value);
            },
            /**
             * @param non-empty-string|Element $value
             * @return TObj
             */
            function (string|Element $value) use ($context, $properties) : object {
                return $this->from(
                    $context,
                    $properties,
                    ($value instanceof Element ? $value : Element::fromString($value))
                );
            }
        );
    }

    /**
     * @param TObj|array $data
     * @param array<string, Property> $properties
     *
     * @return non-empty-string
     */
    private function to(Context $context, array $properties, object|array $data): string
    {
        if (is_array($data)) {
            $data = (object) $data;
        }
        $isAnyPropertyQualified = any(
            $properties,
            static fn (Property $property): bool => $property->getType()->getMeta()->isQualified()->unwrapOr(false)
        );
        $defaultAction = writeChildren([]);

        return (new XsdTypeXmlElementWriter())(
            $context,
            writeChildren(
                [
                    (new XsiAttributeBuilder(
                        $context,
                        XsiTypeDetector::detectFromValue($context, []),
                        includeXsiTargetNamespace: !$isAnyPropertyQualified,
                    )),
                    ...map(
                        $properties,
                        function (Property $property) use ($context, $data, $defaultAction) : Closure {
                            $type = $property->getType();
                            $meta = $type->getMeta();
                            $isAttribute = $meta->isAttribute()->unwrapOr(false);

                            /** @var mixed $value */
                            $value = $this->runLens(
                                property(PhpPropertyNameNormalizer::normalize($property->getName())),
                                $meta,
                                $data,
                                null
                            );

                            return match(true) {
                                $isAttribute => $value ? (new AttributeBuilder(
                                    $type,
                                    $this->grabIsoForProperty($context, $property)->to($value)
                                ))(...) : $defaultAction,
                                $property->getName() === '_' => $value
                                    ? buildValue($this->grabIsoForProperty($context, $property)->to($value))
                                    : (new NilAttributeBuilder())(...),
                                default => $value ? raw($this->grabIsoForProperty($context, $property)->to($value)) : $defaultAction
                            };
                        }
                    )
                ]
            )
        );
    }

    /**
     * @param array<string, Property> $properties
     *
     * @return TObj
     */
    private function from(Context $context, array $properties, Element $data): object
    {
        $nodes = (new DocumentToLookupArrayReader())($data);
        /** @var Iso<TObj, array<string, mixed>> $objectData */
        $objectData = object_data($this->className);

        return $objectData->from(
            pull(
                $properties,
                function (Property $property) use ($context, $nodes): mixed {
                    $type = $property->getType();
                    $meta = $type->getMeta();

                    /** @var string|null $value */
                    $value = $this->runLens(
                        index($property->getName()),
                        $meta,
                        $nodes,
                        null
                    );
                    $defaultValue = $meta->isList()->unwrapOr(false) ? [] : null;

                    /** @psalm-suppress PossiblyNullArgument */
                    return match(true) {
                        $meta->isAttribute()->unwrapOr(false) => $this->grabIsoForProperty($context, $property)->from($value),
                        default => $value !== null ? $this->grabIsoForProperty($context, $property)->from($value) : $defaultValue,
                    };
                },
                static fn (Property $property) => PhpPropertyNameNormalizer::normalize($property->getName()),
            )
        );
    }

    /**
     * @return Iso<mixed, string>
     */
    private function grabIsoForProperty(Context $context, Property $property): Iso
    {
        $propertyContext = $context->withType($property->getType());
        $encoder = $context->registry->detectEncoderForContext($propertyContext);

        return $encoder->iso($propertyContext);
    }

    private function runLens(Lens $lens, TypeMeta $meta, mixed $data, mixed $default): mixed
    {
        try {
            /** @var mixed */
            return $this->decorateLensForType($lens, $meta)->get($data);
        } catch (Exception $e) {
            return $default;
        }
    }

    /**
     * @template S
     * @template A
     *
     * @param Lens<S, A> $lens
     *
     * @return Lens<S, A>
     */
    private function decorateLensForType(Lens $lens, TypeMeta $meta): Lens
    {
        if ($meta->isNullable()->unwrapOr(false)) {
            return optional($lens);
        }

        if (
            $meta->isAttribute()->unwrapOr(false) &&
            $meta->use()->unwrapOr('optional') === 'optional'
        ) {
            return optional($lens);
        }

        return $lens;
    }

    /**
     * @return array<string, Property>
     */
    private function detectProperties(Context $context): array
    {
        $type = (new ComplexTypeBuilder())($context);

        return reindex(
            sort_by(
                $type->getProperties(),
                static fn (Property $property): bool => !$property->getType()->getMeta()->isAttribute()->unwrapOr(false),
            ),
            static fn (Property $property): string => $property->getName(),
        );
    }
}
