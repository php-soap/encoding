<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Encoder\ObjectAccess;
use Soap\Encoding\Encoder\ObjectEncoder;
use Soap\Encoding\Test\Unit\ContextCreatorTrait;
use Soap\Encoding\Xml\Node\ElementLookup;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use stdClass;

#[CoversClass(ObjectEncoder::class), CoversClass(ObjectAccess::class), CoversClass(ElementLookup::class)]
final class ObjectXmlLookupTest extends TestCase
{
    use ContextCreatorTrait;

    /** @return iterable<string, array{string, string, object}> */
    public static function lookup_cases(): iterable
    {
        yield 'child type survives xsi:type' => [
            '<sequence><element name="type" type="xsd:string" minOccurs="0"/></sequence>',
            '<testType xmlns:tns="https://test" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="tns:testType"><type>child</type></testType>',
            (object) ['type' => 'child'],
        ];
        yield 'protocol type does not invent missing child' => [
            '<sequence><element name="type" type="xsd:string" minOccurs="0"/></sequence>',
            '<testType xmlns:tns="https://test" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="tns:testType"/>',
            (object) ['type' => null],
        ];
        yield 'ordinary attribute does not invent missing child' => [
            '<sequence><element name="type" type="xsd:string" minOccurs="0"/></sequence>',
            '<testType type="attribute"/>',
            (object) ['type' => null],
        ];
        yield 'ordinary attribute does not overwrite a child' => [
            '<sequence><element name="type" type="xsd:string" minOccurs="0"/></sequence>',
            '<testType type="attribute"><type>child</type></testType>',
            (object) ['type' => 'child'],
        ];
        yield 'attribute does not invent a missing repeated child' => [
            '<sequence><element name="type" type="xsd:string" minOccurs="0" maxOccurs="unbounded"/></sequence>',
            '<testType type="attribute"/>',
            (object) ['type' => []],
        ];
        yield 'namespace declaration does not invent missing child' => [
            '<sequence><element name="tns" type="xsd:string" minOccurs="0"/></sequence>',
            '<testType xmlns:tns="https://test"/>',
            (object) ['tns' => null],
        ];
        yield 'child does not stand in for a missing attribute' => [
            '<sequence><element name="v" type="xsd:string" minOccurs="0"/></sequence><attribute name="label" type="xsd:string"/>',
            '<testType><v>x</v><label>child</label></testType>',
            (object) ['label' => null, 'v' => 'x'],
        ];
        yield 'simple content remains available' => [
            '<simpleContent><extension base="xsd:string"><attribute name="label" type="xsd:string"/></extension></simpleContent>',
            '<testType label="attribute">content</testType>',
            (object) ['_' => 'content', 'label' => 'attribute'],
        ];
        yield 'unqualified schema type attribute survives xsi:type' => [
            '<attribute name="type" type="xsd:string" use="required"/>',
            '<testType xmlns:tns="https://test" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" type="schema" xsi:type="tns:testType"/>',
            (object) ['type' => 'schema'],
        ];
        yield 'unqualified schema type attribute does not read xsi:type' => [
            '<attribute name="type" type="xsd:string"/>',
            '<testType xmlns:tns="https://test" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="tns:testType"/>',
            (object) ['type' => null],
        ];
        yield 'qualified schema attribute ignores unqualified namesake' => [
            '<attribute name="label" type="xsd:string" form="qualified" use="required"/>',
            '<testType xmlns:t="https://test" t:label="correct" label="wrong"/>',
            (object) ['label' => 'correct'],
        ];
        yield 'unqualified schema attribute ignores qualified namesake' => [
            '<attribute name="label" type="xsd:string" use="required"/>',
            '<testType xmlns:t="https://test" label="correct" t:label="wrong"/>',
            (object) ['label' => 'correct'],
        ];
        yield 'qualified schema attribute in the wrong namespace decodes to null' => [
            '<attribute name="label" type="xsd:string" form="qualified"/>',
            '<testType label="sent unqualified"/>',
            (object) ['label' => null],
        ];
        yield 'unqualified schema attribute in the wrong namespace decodes to null' => [
            '<attribute name="label" type="xsd:string"/>',
            '<testType xmlns:a="urn:a" a:label="sent qualified"/>',
            (object) ['label' => null],
        ];
    }

    #[Test, DataProvider('lookup_cases')]
    public function default_decoder_lenses_distinguish_children_and_attribute_namespaces(string $properties, string $xml, object $expected): void
    {
        $schema = '<complexType name="testType">' . $properties . '</complexType>';
        $metadata = self::createMetadataFromWsdl($schema, 'type="tns:testType"');
        $context = self::createContextFromMetadata($metadata, 'testType')->withBindingUse(BindingUse::LITERAL);
        $iso = new ObjectEncoder(stdClass::class)->iso($context);

        $actual = $iso->from($xml);

        static::assertEquals($expected, $actual);
    }

    #[Test]
    public function nested_repeated_type_children_survive_protocol_type_attributes(): void
    {
        $schema = <<<'XML'
        <complexType name="Child"><sequence>
            <element name="type" type="xsd:string"/>
        </sequence></complexType>
        <complexType name="testType"><sequence>
            <element name="child" type="tns:Child" maxOccurs="unbounded"/>
        </sequence></complexType>
        XML;
        $metadata = self::createMetadataFromWsdl($schema, 'type="tns:testType"');
        $context = self::createContextFromMetadata($metadata, 'testType')->withBindingUse(BindingUse::LITERAL);
        $iso = new ObjectEncoder(stdClass::class)->iso($context);
        $xml = <<<'XML'
        <testType xmlns:tns="https://test" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <child xsi:type="tns:Child"><type>first</type></child>
            <child xsi:type="tns:Child"><type>second</type></child>
        </testType>
        XML;

        $actual = $iso->from($xml);

        static::assertEquals((object) [
            'child' => [(object) ['type' => 'first'], (object) ['type' => 'second']],
        ], $actual);
    }
}
