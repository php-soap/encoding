<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Reader;

use Psl\Collection\Map;
use VeeWee\Xml\Dom\Document;
use function VeeWee\Xml\Dom\Locator\Attribute\attributes_list;
use function VeeWee\Xml\Dom\Locator\Element\children as readChildElements;

final class DocumentToLookupArrayReader
{
    /**
     * @param string $document
     * @return array<string, string>
     */
    public function __invoke(string $xml): array
    {
        $doc = Document::fromXmlString($xml);
        $root = $doc->locateDocumentElement();
        $nodes = [];

        foreach ($elements = readChildElements($root) as $element) {
            $key = $element->localName;
            $nodeValue = Document::fromXmlNode($element)->stringifyDocumentElement();
            // For list-nodes, a concatenated string of the xml nodes will be generated.
            $value = array_key_exists($key, $nodes) ? $nodes[$key].$nodeValue : $nodeValue;
            $nodes[$key] = $value;
        }

        // TODO : Get element value when there are no children but attributes

        foreach (attributes_list($root) as $attribute) {
            $nodes[$attribute->localName] = $attribute->value;
        }

        return $nodes;
    }
}
