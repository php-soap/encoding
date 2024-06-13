<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Node;

use DOMElement;
use Stringable;
use VeeWee\Xml\Dom\Document;
use function Psl\invariant;

final class Element implements Stringable
{
    private ?DOMElement $element = null;
    /**
     * @var non-empty-string|null
     */
    private ?string $value = null;

    private function __construct()
    {
    }

    /**
     * @param non-empty-string $xml
     */
    public static function fromString(string $xml): Element
    {
        $new =  new self();
        $new->element = null;
        $new->value = $xml;

        return $new;
    }

    public static function fromDOMElement(DOMElement $element): self
    {
        $new =  new self();
        $new->element = $element;
        $new->value = null;

        return $new;
    }

    public function element(): DOMElement
    {
        if (!$this->element) {
            invariant($this->value !== null, 'Expected an XML value to be present');
            $this->element = Document::fromXmlString($this->value)->locateDocumentElement();
        }

        return $this->element;
    }

    /**
     * @return non-empty-string
     */
    public function value(): string
    {
        if ($this->value === null) {
            invariant($this->element !== null, 'Expected an DOMElement to be present');
            $this->value = Document::fromXmlNode($this->element)->stringifyDocumentElement();
        }

        return $this->value;
    }

    public function document(): Document
    {
        return Document::fromUnsafeDocument($this->element()->ownerDocument);
    }

    /**
     * @return non-empty-string
     */
    public function __toString()
    {
        return $this->value();
    }
}
