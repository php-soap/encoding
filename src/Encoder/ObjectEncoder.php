<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\Xml\XsdTypeXmlElementBuilder;
use Soap\Engine\Metadata\Model\Property;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Xml\Dom\Document;
use function Psl\Dict\merge;
use function Psl\Dict\reindex;
use function Psl\invariant;
use function Psl\Dict\map;
use function Psl\Iter\reduce;
use function VeeWee\Reflecta\Iso\object_data;
use function VeeWee\Reflecta\Lens\index;
use function VeeWee\Xml\Dom\Locator\Element\children as readChildren;
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
            function (object $value) use ($context, $properties) : string {
                return (new XsdTypeXmlElementBuilder($context->type))(
                    writeChildren(
                        map(
                            $properties,
                            fn (Property $property) => raw(
                                $this->grabIsoForProperty($context, $property)->to(
                                    property($property->getName())->get($value)
                                )
                            )
                        )
                    )
                );
            },
            function (string $value) use ($context, $properties) : object {
                $doc = Document::fromXmlString($value);

                // Convert the XML into a lookup hash per property.
                // For list-nodes, a concatenated string of the xml nodes will be generated.
                $nodes = reduce(
                    readChildren($doc->locateDocumentElement()),
                    static function (array $lookup, \DOMElement $element) use ($doc): array {
                        $key = $element->localName;
                        $nodeValue = $doc->stringifyNode($element);
                        $value = array_key_exists($key, $lookup) ? $lookup[$key].$nodeValue : $nodeValue;

                        return merge($lookup, [$key => $value]);
                    },
                    []
                );

                return object_data($this->className)->from(
                    map(
                        $properties,
                        fn (Property $property) => $this->grabIsoForProperty($context, $property)->from(
                            index($property->getName())
                                ->tryGet($nodes)
                                ->catch(static function () {
                                    // TODO : Improve logic based on 'list' or 'nullable' or ...
                                    // TODO : - what with nullables that are not there e.g.
                                    // TODO : - what with nullable?
                                    return '';
                                })
                                ->getResult()
                        )
                    )
                );
            }
        );
    }

    private function grabIsoForProperty(Context $context, Property $property): Iso
    {
        $encoder = $context->registry->findByXsdType($property->getType());

        return $encoder->iso(
            $context->withType($property->getType())
        );
    }
}
