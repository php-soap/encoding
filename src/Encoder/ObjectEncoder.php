<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Closure;
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
use function Psl\Dict\reindex;
use function Psl\invariant;
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
 * @implements XmlEncoder<string, object|array>
 */
final class ObjectEncoder implements XmlEncoder
{
    /**
     * @param class-string<object|array> $className
     */
    public function __construct(
        private readonly string $className
    ) {
    }

    public function iso(Context $context): Iso
    {
        $properties = $this->detectProperties($context);

        return new Iso(
            function (object|array $value) use ($context, $properties) : string {
                return $this->to($context, $properties, $value);
            },
            function (string $value) use ($context, $properties) : object {
                return $this->from($context, $properties, $value);
            }
        );
    }

    /**
     * @param array<string, Property> $properties
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
                            $lens = $this->decorateLensForType(property($property->getName()), $type);
                            $value = $lens
                                ->tryGet($data)
                                ->catch(static fn () => null)
                                ->getResult();

                            return $this->handleProperty(
                                $property,
                                onAttribute: fn (): Closure => $value ? (new AttributeBuilder(
                                    $context,
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
     */
    private function from(Context $context, array $properties, string $data): object
    {
        $nodes = (new DocumentToLookupArrayReader())($data);

        return object_data($this->className)->from(
            map(
                $properties,
                function (Property $property) use ($context, $nodes): mixed {
                    $type = $property->getType();
                    $meta = $type->getMeta();
                    $isList = $meta->isList()->unwrapOr(false);
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
                        onAttribute: fn (): mixed => $this->grabIsoForProperty($context, $property)->from($value),
                        onValue: fn (): mixed => $value !== null ? $this->grabIsoForProperty($context, $property)->from($value) : $defaultValue,
                        onElements: fn (): mixed => $value !== null ? $this->grabIsoForProperty($context, $property)->from($value) : $defaultValue,
                    );
                }
            )
        );
    }

    private function grabIsoForProperty(Context $context, Property $property): Iso
    {
        $propertyContext = $context->withType($property->getType());
        $encoder = $context->registry->detectEncoderForContext($propertyContext);

        return $encoder->iso($propertyContext);
    }

    /**
     * @template T
     *
     * @param Closure(): T $onAttribute
     * @param Closure(): T $onValue
     * @param Closure(): T $onElements
     * @return T
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
     * @param Lens<mixed, mixed> $lens
     *
     * @return Lens<mixed, mixed>
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
