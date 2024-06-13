<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Reader;

use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Node\ElementList;
use function VeeWee\Xml\Dom\Locator\Attribute\attributes_list;
use function VeeWee\Xml\Dom\Locator\Element\children as readChildElements;

final class DocumentToLookupArrayReader
{
    /**
     * @return array<string, string|Element|ElementList>
     */
    public function __invoke(Element $xml): array
    {
        $root = $xml->element();
        /** @var array<string, string|Element|ElementList> $nodes */
        $nodes = [];

        // Read all child elements.
        // The key is the name of the elements
        // The value is the raw XML for those element(s)
        $elements = readChildElements($root);
        foreach ($elements as $element) {
            $key = $element->localName ?? 'unknown';
            $previousValue = $nodes[$key] ?? null;
            $currentElement = Element::fromDOMElement($element);

            // Incrementally build up lists.
            /** @var string|Element|ElementList $value */
            $value = match(true) {
                $previousValue instanceof ElementList => $previousValue->append($currentElement),
                $previousValue instanceof Element => new ElementList($previousValue, $currentElement),
                default => $currentElement

            };
            $nodes[$key] = $value;
        }

        // It might be possible that the child is a regular textNode.
        // In that case, we use '_' as the key and the value of the textNode as value.
        $content = trim($root->textContent);
        if (!$elements->count() && $content) {
            $nodes['_'] = $content;
        }

        // All attributes also need to be added as key => value pairs.
        foreach (attributes_list($root) as $attribute) {
            $key = $attribute->localName ?? 'unkown';
            $nodes[$key] = $attribute->value;
        }

        return $nodes;
    }
}
