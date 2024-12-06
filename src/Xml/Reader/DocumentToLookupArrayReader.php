<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Reader;

use DOMAttr;
use DOMNode;
use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Node\ElementList;
use function VeeWee\Xml\Dom\Predicate\is_element;

/**
 * @psalm-type LookupArray = array<string, string|Element|ElementList>
 */
final class DocumentToLookupArrayReader
{
    /**
     * @return LookupArray
     */
    public function __invoke(Element $xml): array
    {
        $root = $xml->element();
        /** @var array<string, string|Element|ElementList> $nodes */
        $nodes = [];

        // Read all child elements.
        // The key is the name of the elements
        // The value is the raw XML for those element(s)
        /** @var iterable<DOMNode> $children */
        $children = $root->childNodes;
        foreach ($children as $element) {
            if (!is_element($element)) {
                continue;
            }

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
        if (!$nodes && $content = trim($root->textContent)) {
            $nodes['_'] = $content;
        }

        // All attributes also need to be added as key => value pairs.
        /** @var \iterable<DOMAttr> $attributes */
        $attributes = $root->attributes;
        foreach ($attributes as $attribute) {
            $key = $attribute->localName ?? 'unknown';
            $nodes[$key] = $attribute->value;
        }

        return $nodes;
    }
}
