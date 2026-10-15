<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Node;

use Dom\Element as DomElement;
use Dom\Node;
use VeeWee\Xml\Xmlns\Xmlns;
use function VeeWee\Xml\Dom\Predicate\is_element;

/**
 * Read model of one XML element that is being decoded:
 * child elements by local name, attributes by namespace and local name, and the text content.
 * Attributes and child elements never stand in for each other.
 */
final readonly class ElementLookup
{
    /**
     * @param array<string, Element|ElementList> $children Keyed by local name, in document order.
     */
    private function __construct(
        private DomElement $root,
        private array $children,
    ) {
    }

    public static function fromElement(Element $xml): self
    {
        $root = $xml->element();
        $children = [];

        /** @var iterable<Node> $childNodes */
        $childNodes = $root->childNodes;
        foreach ($childNodes as $node) {
            if (!is_element($node)) {
                continue;
            }

            $key = $node->localName;
            $previous = $children[$key] ?? null;
            $current = Element::fromDOMElement($node);

            $children[$key] = match(true) {
                $previous instanceof ElementList => $previous->append($current),
                $previous instanceof Element => new ElementList($previous, $current),
                default => $current,
            };
        }

        return new self($root, $children);
    }

    /**
     * The child element with this local name, or the list of them when it repeats. Null when there is none.
     */
    public function element(string $name): Element|ElementList|null
    {
        return $this->children[$name] ?? null;
    }

    /**
     * Every child element, keyed by local name in document order. Repeated children are grouped in one list.
     *
     * @return array<string, Element|ElementList>
     */
    public function children(): array
    {
        return $this->children;
    }

    /**
     * The value of the attribute in this namespace, where '' is the attribute without namespace.
     * Null when absent, also when an attribute with this local name exists in another namespace.
     */
    public function attribute(string $localName, string $namespace = ''): ?string
    {
        return $this->root->getAttributeNS($namespace, $localName);
    }

    /**
     * The text content of an element without child elements. Null when it has child elements or is xsi:nil.
     */
    public function text(): ?string
    {
        if ($this->children || $this->root->getAttributeNS(Xmlns::xsi()->value(), 'nil') === 'true') {
            return null;
        }

        return $this->root->textContent ?? '';
    }
}
