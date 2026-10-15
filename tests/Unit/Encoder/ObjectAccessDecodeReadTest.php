<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\ObjectAccess;
use Soap\Encoding\EncoderRegistry;
use Soap\Encoding\Test\Fixture\Encoder\ThrowingLensEncoder;
use Soap\Encoding\Test\Unit\ContextCreatorTrait;
use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Node\ElementLookup;
use Soap\WsdlReader\Model\Definitions\BindingUse;

#[CoversClass(ObjectAccess::class)]
final class ObjectAccessDecodeReadTest extends TestCase
{
    use ContextCreatorTrait;

    private const SCHEMA = <<<'XML'
    <import namespace="http://www.w3.org/XML/1998/namespace"/>
    <complexType name="mixedType">
        <sequence>
            <element name="element" type="xsd:string" minOccurs="0"/>
            <element name="list" type="xsd:string" maxOccurs="unbounded"/>
            <element name="custom" type="tns:customType"/>
        </sequence>
        <attribute name="plain" type="xsd:string"/>
        <attribute name="qualified" type="xsd:string" form="qualified"/>
        <attribute ref="xml:lang"/>
    </complexType>
    <complexType name="textType">
        <simpleContent>
            <extension base="xsd:string">
                <attribute name="plain" type="xsd:string"/>
            </extension>
        </simpleContent>
    </complexType>
    <complexType name="customType">
        <simpleContent><extension base="xsd:string"/></simpleContent>
    </complexType>
    XML;

    #[Test]
    public function built_in_properties_decode_by_element_attribute_or_text_and_custom_lenses_by_their_lens(): void
    {
        static::assertSame(
            [
                'plain' => [ObjectAccess::DECODE_ATTRIBUTE, 'plain', ''],
                'qualified' => [ObjectAccess::DECODE_ATTRIBUTE, 'qualified', 'https://test'],
                'lang' => [ObjectAccess::DECODE_ATTRIBUTE, 'lang', 'http://www.w3.org/XML/1998/namespace'],
                'element' => [ObjectAccess::DECODE_ELEMENT, 'element', ''],
                'list' => [ObjectAccess::DECODE_ELEMENT, 'list', ''],
                'custom' => null,
            ],
            ObjectAccess::forContext(self::context('mixedType'))->decodeReads
        );
        static::assertSame(
            ['plain' => [ObjectAccess::DECODE_ATTRIBUTE, 'plain', ''], '_' => [ObjectAccess::DECODE_TEXT, '_', '']],
            ObjectAccess::forContext(self::context('textType'))->decodeReads
        );
    }

    /** @return iterable<string, array{string, string}> */
    public static function lookups(): iterable
    {
        yield 'every property present' => [
            'mixedType',
            '<mixedType xmlns:t="https://test" plain="p" t:qualified="q" xml:lang="nl">'
                . '<element>e</element><list>1</list><list>2</list><custom>c</custom></mixedType>',
        ];
        yield 'every property absent' => ['mixedType', '<mixedType/>'];
        yield 'attributes in the wrong namespace' => [
            'mixedType',
            '<mixedType xmlns:t="https://test" t:plain="p" qualified="q" lang="nl"/>',
        ];
        yield 'text present' => ['textType', '<textType plain="p">content</textType>'];
        yield 'text nil' => [
            'textType',
            '<textType xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:nil="true"/>',
        ];
        yield 'text with child elements' => ['textType', '<textType><child/></textType>'];
    }

    #[Test, DataProvider('lookups')]
    public function built_in_decoder_lenses_read_the_same_value_as_the_direct_read(string $typeName, string $xml): void
    {
        $access = ObjectAccess::forContext(self::context($typeName));
        $lookup = ElementLookup::fromElement(Element::fromString($xml));

        foreach ($access->decodeReads as $property => $read) {
            if ($read === null) {
                continue;
            }

            $direct = match ($read[0]) {
                ObjectAccess::DECODE_ELEMENT => $lookup->element($read[1]),
                ObjectAccess::DECODE_ATTRIBUTE => $lookup->attribute($read[1], $read[2]),
                ObjectAccess::DECODE_TEXT => $lookup->text(),
            };

            static::assertSame($direct, $access->decoderLenses[$property]->get($lookup), $property);
        }
    }

    private static function context(string $typeName): Context
    {
        $metadata = self::createMetadataFromWsdl(self::SCHEMA, 'type="tns:'.$typeName.'"');
        $registry = EncoderRegistry::default()
            ->addComplexTypeConverter('https://test', 'customType', new ThrowingLensEncoder());

        return new Context(
            $metadata->getTypes()->fetchByNameAndXmlNamespace($typeName, 'https://test')->getXsdType(),
            $metadata,
            $registry,
            self::buildNamespaces(),
            BindingUse::LITERAL,
        );
    }
}
