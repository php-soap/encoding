<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Node;

use Dom\Attr;
use Dom\Element as DomElement;
use Dom\Node;
use Soap\Encoding\Xml\Reader\DocumentToLookupArrayReader;
use VeeWee\Xml\Xmlns\Xmlns;
use function array_key_exists;
use function VeeWee\Xml\Dom\Predicate\is_element;

/**
 * Read model of one XML element that is being decoded:
 * child elements by local name, attributes by namespace and local name, and the text content.
 * Attributes and child elements never stand in for each other.
 *
 * @psalm-import-type LookupArray from DocumentToLookupArrayReader
 */
final readonly class ElementLookup
{
    private const SOAP_ENC_11 = 'http://schemas.xmlsoap.org/soap/encoding/';
    private const SOAP_ENC_12 = 'http://www.w3.org/2003/05/soap-encoding';

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
     * When that one is absent, the only other attribute with this local name is used,
     * so a server that qualifies an attribute differently from its schema still decodes.
     * Namespace declarations and xsi or soap-enc attributes never count as that other attribute.
     * Null when absent or when the local name is ambiguous.
     */
    public function attribute(string $localName, string $namespace = ''): ?string
    {
        $exact = $this->root->getAttributeNS($namespace, $localName);
        if ($exact !== null) {
            return $exact;
        }

        $found = null;
        /** @var iterable<Attr> $attributes */
        $attributes = $this->root->attributes;
        foreach ($attributes as $attribute) {
            if ($attribute->localName !== $localName || self::isProtocolNamespace($attribute->namespaceURI)) {
                continue;
            }

            if ($found !== null) {
                return null;
            }

            $found = $attribute->value;
        }

        return $found;
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

    /**
     * The element as one flat array, the shape that DocumentToLookupArrayReader returns:
     * child elements by local name, the text under '_', and every other attribute by local name.
     * A child element wins from an attribute with the same local name.
     * Namespace declarations and xsi or soap-enc attributes are left out.
     *
     * @return LookupArray
     */
    public function toArray(): array
    {
        $nodes = $this->children;

        $text = $this->text();
        if ($text !== null) {
            $nodes['_'] = $text;
        }

        /** @var iterable<Attr> $attributes */
        $attributes = $this->root->attributes;
        foreach ($attributes as $attribute) {
            $key = $attribute->localName;
            if (array_key_exists($key, $nodes) || self::isProtocolNamespace($attribute->namespaceURI)) {
                continue;
            }

            $nodes[$key] = $attribute->value;
        }

        return $nodes;
    }

    private static function isProtocolNamespace(?string $namespace): bool
    {
        return match ($namespace) {
            Xmlns::xmlns()->value(), Xmlns::xsi()->value(), self::SOAP_ENC_11, self::SOAP_ENC_12 => true,
            default => false,
        };
    }
}
