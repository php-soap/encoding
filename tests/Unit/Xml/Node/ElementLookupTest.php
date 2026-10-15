<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Xml\Node;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Node\ElementList;
use Soap\Encoding\Xml\Node\ElementLookup;

#[CoversClass(ElementLookup::class)]
final class ElementLookupTest extends TestCase
{
    private const XSI = 'http://www.w3.org/2001/XMLSchema-instance';

    #[Test]
    public function it_finds_child_elements_by_local_name(): void
    {
        $lookup = self::lookup('<item xmlns:t="urn:t"><a>1</a><t:b>2</t:b></item>');

        $a = $lookup->element('a');
        $b = $lookup->element('b');

        static::assertInstanceOf(Element::class, $a);
        static::assertSame('<a>1</a>', $a->value());
        static::assertInstanceOf(Element::class, $b);
        static::assertSame('2', $b->element()->textContent);
        static::assertNull($lookup->element('c'));
    }

    #[Test]
    public function it_groups_repeated_children_in_a_list(): void
    {
        $lookup = self::lookup('<item><a>1</a><b>x</b><a>2</a><a>3</a></item>');

        $a = $lookup->element('a');

        static::assertInstanceOf(ElementList::class, $a);
        static::assertSame(['1', '2', '3'], $a->traverse(static fn (Element $e): string => $e->element()->textContent));
        static::assertInstanceOf(Element::class, $lookup->element('b'));
    }

    #[Test]
    public function it_never_returns_an_attribute_as_element(): void
    {
        $lookup = self::lookup('<item xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="T" label="x" xsi:nil="false"/>');

        static::assertNull($lookup->element('type'));
        static::assertNull($lookup->element('label'));
        static::assertNull($lookup->element('nil'));
        static::assertNull($lookup->element('xsi'));
    }

    #[Test]
    public function it_returns_all_children_by_local_name_in_document_order(): void
    {
        $lookup = self::lookup('<item label="x"><b>1</b><a>2</a><b>3</b>text</item>');

        $children = $lookup->children();

        static::assertSame(['b', 'a'], array_keys($children));
        static::assertInstanceOf(ElementList::class, $children['b']);
        static::assertCount(2, $children['b']);
        static::assertSame($lookup->element('a'), $children['a']);
    }

    /**
     * @return iterable<string, array{string, string|null}>
     */
    public static function text_cases(): iterable
    {
        yield 'text' => ['<item>hello</item>', 'hello'];
        yield 'empty element' => ['<item/>', ''];
        yield 'text next to attributes' => ['<item label="x">hello</item>', 'hello'];
        yield 'child elements' => ['<item><a>1</a></item>', null];
        yield 'mixed content' => ['<item>hello <a>1</a></item>', null];
        yield 'nil' => ['<item xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:nil="true"/>', null];
        yield 'nil false' => ['<item xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:nil="false">hello</item>', 'hello'];
    }

    #[Test]
    #[DataProvider('text_cases')]
    public function it_reads_the_text_of_an_element_without_children(string $xml, ?string $expected): void
    {
        static::assertSame($expected, self::lookup($xml)->text());
    }

    /**
     * @return iterable<string, array{string, string, string, string|null}>
     */
    public static function attribute_cases(): iterable
    {
        yield 'no namespace' => ['<item id="1"/>', 'id', '', '1'];
        yield 'empty value' => ['<item id=""/>', 'id', '', ''];
        yield 'absent' => ['<item other="1"/>', 'id', '', null];
        yield 'namespaced by uri, not by prefix' => ['<item xmlns:t="urn:t" t:id="1"/>', 'id', 'urn:t', '1'];
        yield 'exact namespace wins over a namesake' => ['<item xmlns:a="urn:a" id="1" a:id="2"/>', 'id', '', '1'];
        yield 'exact target namespace wins over a namesake' => ['<item xmlns:a="urn:a" id="1" a:id="2"/>', 'id', 'urn:a', '2'];
        yield 'two namespaces, same local name' => ['<item xmlns:a="urn:a" xmlns:b="urn:b" a:id="2" b:id="3"/>', 'id', 'urn:b', '3'];
        yield 'single namesake in another namespace' => ['<item xml:lang="nl"/>', 'lang', '', 'nl'];
        yield 'single unqualified namesake of a qualified attribute' => ['<item id="1"/>', 'id', 'urn:t', '1'];
        yield 'ambiguous namesakes' => ['<item xmlns:a="urn:a" xmlns:b="urn:b" a:id="2" b:id="3"/>', 'id', '', null];
        yield 'xmlns declaration is not an attribute' => ['<item xmlns:id="urn:id"/>', 'id', '', null];
        yield 'xsi attribute is not a namesake' => ['<item xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="T"/>', 'type', '', null];
        yield 'soap-enc 1.1 attribute is not a namesake' => ['<item xmlns:enc="http://schemas.xmlsoap.org/soap/encoding/" enc:arrayType="x[1]"/>', 'arrayType', '', null];
        yield 'soap-enc 1.2 attribute is not a namesake' => ['<item xmlns:enc="http://www.w3.org/2003/05/soap-encoding" enc:itemType="x"/>', 'itemType', '', null];
        yield 'xsi attribute by its own namespace' => ['<item xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="T"/>', 'type', self::XSI, 'T'];
        yield 'child element is not an attribute' => ['<item><id>1</id></item>', 'id', '', null];
    }

    #[Test]
    #[DataProvider('attribute_cases')]
    public function it_reads_attributes_by_namespace_with_a_single_namesake_fallback(
        string $xml,
        string $localName,
        string $namespace,
        ?string $expected
    ): void {
        static::assertSame($expected, self::lookup($xml)->attribute($localName, $namespace));
    }

    #[Test]
    public function it_keeps_repeated_children_and_namespaced_attributes_apart(): void
    {
        $lookup = self::lookup('<item xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="Record" type="attribute"><type>first</type><type>second</type></item>');

        $type = $lookup->element('type');

        static::assertInstanceOf(ElementList::class, $type);
        static::assertCount(2, $type);
        static::assertSame('attribute', $lookup->attribute('type'));
        static::assertSame('Record', $lookup->attribute('type', self::XSI));
    }

    #[Test]
    public function it_keeps_nonconflicting_attributes_in_the_array(): void
    {
        $lookup = self::lookup('<item label="text"/>');

        static::assertSame('text', $lookup->toArray()['label']);
        static::assertSame('text', $lookup->attribute('label'));
    }

    #[Test]
    public function it_builds_the_legacy_array(): void
    {
        $lookup = self::lookup(
            '<item xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:enc="http://schemas.xmlsoap.org/soap/encoding/"'
            . ' xmlns:enc12="http://www.w3.org/2003/05/soap-encoding" xmlns:a="urn:a"'
            . ' xsi:type="T" enc:arrayType="x[1]" enc12:itemType="x" type="attr" label="l" a:code="c" a:label="other">'
            . '<type>child</type><b>1</b><b>2</b>'
            . '</item>'
        );

        $array = $lookup->toArray();

        static::assertSame(['type', 'b', 'label', 'code'], array_keys($array));
        static::assertSame($lookup->element('type'), $array['type']);
        static::assertSame($lookup->element('b'), $array['b']);
        static::assertSame('l', $array['label']);
        static::assertSame('c', $array['code']);
    }

    /**
     * @return iterable<string, array{string, array<string, string>}>
     */
    public static function legacy_text_cases(): iterable
    {
        yield 'text and attribute' => ['<item label="l">hello</item>', ['_' => 'hello', 'label' => 'l']];
        yield 'empty element' => ['<item/>', ['_' => '']];
        yield 'nil' => ['<item xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:nil="true"/>', []];
    }

    /**
     * @param array<string, string> $expected
     */
    #[Test]
    #[DataProvider('legacy_text_cases')]
    public function it_puts_text_under_underscore_in_the_legacy_array(string $xml, array $expected): void
    {
        static::assertSame($expected, self::lookup($xml)->toArray());
    }

    /**
     * @param non-empty-string $xml
     */
    private static function lookup(string $xml): ElementLookup
    {
        return ElementLookup::fromElement(Element::fromString($xml));
    }
}
