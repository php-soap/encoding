<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Closure;
use Soap\Encoding\Normalizer\PhpPropertyNameNormalizer;
use Soap\Encoding\TypeInference\ComplexTypeBuilder;
use Soap\Encoding\TypeInference\XsiTypeDetector;
use Soap\Encoding\Xml\Reader\DocumentToLookupArrayReader;
use Soap\Encoding\Xml\Writer\AttributeBuilder;
use Soap\Encoding\Xml\Writer\NilAttributeBuilder;
use Soap\Encoding\Xml\Writer\XsdTypeXmlElementWriter;
use Soap\Encoding\Xml\Writer\XsiAttributeBuilder;
use Soap\Engine\Metadata\Model\Property;
use Soap\Engine\Metadata\Model\XsdType;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Reflecta\Lens\Lens;
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
             * @param non-empty-string $value
             * @return TObj
             */
            function (string $value) use ($context, $properties) : object {
                return $this->from($context, $properties, $value);
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
                            $lens = $this->decorateLensForType(
                                property(PhpPropertyNameNormalizer::normalize($property->getName())),
                                $type
                            );
                            /**
                             * @psalm-var mixed $value
                             * @psalm-suppress PossiblyInvalidArgument - Psalm gets lost in the lens.
                             */
                            $value = $lens
                                ->tryGet($data)
                                ->catch(static fn () => null)
                                ->getResult();

                            return $this->handleProperty(
                                $property,
                                onAttribute: fn (): Closure => $value ? (new AttributeBuilder(
                                    $type,
                                    $this->grabIsoForProperty($context, $property)->to($value)
                                ))(...) : $defaultAction,
                                onValue: fn (): Closure => $value
                                    ? buildValue($this->grabIsoForProperty($context, $property)->to($value))
                                    : (new NilAttributeBuilder())(...),
                                onElements: fn (): Closure => $value ? raw($this->grabIsoForProperty($context, $property)->to($value)) : $defaultAction,
                            );
                        }
                    )
                ]
            )
        );
    }

    /**
     * @param array<string, Property> $properties
     * @param non-empty-string $data
     *
     * @return TObj
     */
    private function from(Context $context, array $properties, string $data): object
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
                    $isList = $meta->isList()->unwrapOr(false);
                    /** @psalm-var string|null $value */
                    $value = $this->decorateLensForType(
                        index($property->getName()),
                        $type
                    )
                        ->tryGet($nodes)
                        ->catch(static fn () => null)
                        ->getResult();
                    $defaultValue = $isList ? [] : null;

                    return $this->handleProperty(
                        $property,
                        onAttribute: fn (): mixed => /** @psalm-suppress PossiblyNullArgument */$this->grabIsoForProperty($context, $property)->from($value),
                        onValue: fn (): mixed => $value !== null ? $this->grabIsoForProperty($context, $property)->from($value) : $defaultValue,
                        onElements: fn (): mixed => $value !== null ? $this->grabIsoForProperty($context, $property)->from($value) : $defaultValue,
                    );
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

    /**
     * @template X
     *
     * @param Closure(): X $onAttribute
     * @param Closure(): X $onValue
     * @param Closure(): X $onElements
     * @return X
     */
    private function handleProperty(
        Property $property,
        Closure $onAttribute,
        Closure $onValue,
        Closure $onElements,
    ) {
        $meta = $property->getType()->getMeta();

        return match(true) {
            $meta->isAttribute()->unwrapOr(false) => $onAttribute(),
            $property->getName() === '_' => $onValue(),
            default => $onElements()
        };
    }

    /**
     * @template S
     * @template A
     *
     * @param Lens<S, A> $lens
     *
     * @return Lens<S, A>
     */
    private function decorateLensForType(Lens $lens, XsdType $type): Lens
    {
        $meta = $type->getMeta();
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
        $properties = reindex(
            sort_by(
                $type->getProperties(),
                static fn (Property $property): bool => !$property->getType()->getMeta()->isAttribute()->unwrapOr(false),
            ),
            static fn (Property $property): string => $property->getName(),
        );

        return $properties;
    }
}
