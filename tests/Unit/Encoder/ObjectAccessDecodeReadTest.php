<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder;

use Closure;
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
use function array_keys;

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
    public function every_property_decodes_through_a_plain_reader(): void
    {
        $mixed = ObjectAccess::forContext(self::context('mixedType'))->decodeReaders;
        $text = ObjectAccess::forContext(self::context('textType'))->decodeReaders;

        static::assertSame(['plain', 'qualified', 'lang', 'element', 'list', 'custom'], array_keys($mixed));
        static::assertSame(['plain', '_'], array_keys($text));
        static::assertContainsOnlyInstancesOf(Closure::class, [...$mixed, ...$text]);
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
    public function built_in_decoder_readers_read_the_same_value_as_the_direct_read(string $typeName, string $xml): void
    {
        $access = ObjectAccess::forContext(self::context($typeName));
        $lookup = ElementLookup::fromElement(Element::fromString($xml));

        $direct = [
            'mixedType' => [
                'plain' => $lookup->attribute('plain', ''),
                'qualified' => $lookup->attribute('qualified', 'https://test'),
                'lang' => $lookup->attribute('lang', 'http://www.w3.org/XML/1998/namespace'),
                'element' => $lookup->element('element'),
                'list' => $lookup->element('list'),
            ],
            'textType' => [
                'plain' => $lookup->attribute('plain', ''),
                '_' => $lookup->text(),
            ],
        ][$typeName];

        foreach ($direct as $property => $expected) {
            static::assertSame($expected, $access->decodeReaders[$property]($lookup), $property);
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
