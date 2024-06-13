<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Xml\Node;

use DOMDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Xml\Node\Element;

#[CoversClass(Element::class)]
final class ElementTest extends TestCase
{

    public function test_it_can_be_constructed_from_xml(): void
    {
        $element = Element::fromString($xml = '<hello>world</hello>');

        static::assertSame($xml, $element->value());
        static::assertSame('hello', $element->element()->nodeName);
        static::assertSame('world', $element->element()->textContent);
        static::assertSame($xml, $element->document()->stringifyDocumentElement());
        static::assertSame($xml, (string) $element);
    }

    public function test_it_can_be_constructed_from_dom_element(): void
    {
        $document = new DOMDocument();
        $document->loadXML($xml = '<hello>world</hello>');
        $element = Element::fromDOMElement($DOMElement = $document->documentElement);

        static::assertSame($DOMElement, $element->element());
        static::assertSame($xml, $element->value());
        static::assertSame($xml, $element->document()->stringifyDocumentElement());
        static::assertSame($xml, (string) $element);
    }
}
