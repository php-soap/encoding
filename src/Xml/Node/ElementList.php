<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Node;

use DOMElement;
use VeeWee\Xml\Dom\Document;
use function VeeWee\Xml\Dom\Locator\Element\children as readChildren;

final class ElementList
{
    /** @var list<Element> */
    private array $elements;

    /**
     * @no-named-arguments
     */
    public function __construct(Element ...$elements)
    {
        $this->elements = $elements;
    }

    /**
     * @param non-empty-string $xml
     */
    public static function fromString(string $xml): self
    {
        $doc = Document::fromXmlString($xml);

        return new self(
            ...readChildren($doc->locateDocumentElement())->map(
                static fn (DOMElement $element): Element => Element::fromDOMElement($element)
            )
        );
    }

    public function append(Element $element): self
    {
        $this->elements[] = $element;

        return $this;
    }

    /**
     * @return list<Element>
     */
    public function elements(): array
    {
        return $this->elements;
    }
}
