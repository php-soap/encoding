<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\Encoder\SimpleType\GuessTypeEncoder;
use Soap\Encoding\Xml\Reader\DocumentToLookupArrayReader;
use Soap\Encoding\Xml\Writer\AttributeBuilder;
use Soap\Encoding\Xml\XsdTypeXmlElementWriter;
use Soap\Engine\Metadata\Model\Property;
use VeeWee\Reflecta\Iso\Iso;
use function Psl\Dict\reindex;
use function Psl\invariant;
use function Psl\Dict\map;
use function VeeWee\Reflecta\Iso\object_data;
use function VeeWee\Reflecta\Lens\index;
use function VeeWee\Xml\Dom\Builder\value;
use function VeeWee\Xml\Writer\Builder\children as writeChildren;
use function VeeWee\Xml\Writer\Builder\raw;
use function VeeWee\Reflecta\Lens\property;

/**
 * TODO : object instead of array?
 * TODO : Support for both?
 * TODO : ...
 * @template T extends object
 *
 * @implements XmlEncoder<string, T>
 */
final class ObjectEncoder implements XmlEncoder
{
    /**
     * @param class-string<T> $className
     */
    public function __construct(
        private readonly string $className
    ) {
    }

    public function iso(Context $context): Iso
    {
        invariant((bool)$context->type->getXmlNamespace(), 'TODO : Expecting a namespace for now');

        $type = $context->metadata->getTypes()->fetchByNameAndXmlNamespace( // TODO : simplify API
            $context->type->getName(),
            $context->type->getXmlNamespace()
        );
        $properties = reindex(
            $type->getProperties(),
            static fn(Property $property): string => $property->getName(),
        );

        return new Iso(
            /**
             * @param T $value
             */
            function (object $value) use ($context, $properties) : string {
                return $this->to($context, $properties, $value);
            },
            /**
             * @return T
             */
            function (string $value) use ($context, $properties) : object {
                return $this->from($context, $properties, $value);
            }
        );
    }

    /**
     * @param array<string, Property> $properties
     */
    private function to(Context $context, array $properties, object $data): string
    {
        return (new XsdTypeXmlElementWriter())(
            $context->type,
            writeChildren(
                map(
                    $properties,
                    function (Property $property) use ($context, $data) : callable {
                        $type = $property->getType();
                        $meta = $type->getMeta();
                        $value = property($property->getName())->get($data);

                        return match(true) {
                            $meta->isAttribute()->unwrapOr(false) => new AttributeBuilder(
                                $type,
                                $this->grabSimpleTypeIsoForProperty($context, $property)->to($value)
                            ),
                            // TODO -> meta->isElementValue() (fix multiple child elements to be isElementValue=false)
                            $property->getName() === '_' => value(
                                $this->grabSimpleTypeIsoForProperty($context, $property)->to($value)
                            ),
                            default => raw(
                                $this->grabIsoForProperty($context, $property)->to($value)
                            )
                        };
                    }
                )
            )
        );
    }

    /**
     * @param array<string, Property> $properties
     * @return T
     */
    private function from(Context $context, array $properties, string $data): object
    {
        $nodes = (new DocumentToLookupArrayReader())($data);

        return object_data($this->className)->from(
            map(
                $properties,
                function (Property $property) use ($context, $nodes): mixed {
                    $meta = $property->getType()->getMeta();
                    $value = index($property->getName())
                        ->tryGet($nodes)
                        ->catch(static function () use ($property) {

                            // TODO : Improve logic based on 'list' or 'nullable' or nullable attributes ...
                            // TODO : - what with nullables that are not there e.g.
                            // TODO : - what with nullable?
                            return '';
                        })
                        ->getResult();

                    return match(true) {
                        $meta->isAttribute()->unwrapOr(false) => $this->grabSimpleTypeIsoForProperty($context, $property)->from($value),
                        default => $this->grabIsoForProperty($context, $property)->from($value)
                    };
                }
            )
        );
    }

    private function grabIsoForProperty(Context $context, Property $property): Iso
    {
        $encoder = $context->registry->findByXsdType($property->getType());

        return $encoder->iso(
            $context->withType($property->getType())
        );
    }

    private function grabSimpleTypeIsoForProperty(Context $context, Property $property): Iso
    {
        return (new GuessTypeEncoder())->iso(
            $context->withType($property->getType())
        );
    }
}
