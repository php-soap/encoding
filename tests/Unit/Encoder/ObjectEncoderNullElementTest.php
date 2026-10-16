<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\ObjectAccess;
use Soap\Encoding\Encoder\ObjectEncoder;
use Soap\Encoding\EncoderRegistry;
use Soap\Encoding\Test\Fixture\Encoder\NullWritingEncoder;
use Soap\Encoding\Test\Fixture\Encoder\ThrowingLensEncoder;
use Soap\Encoding\Test\Unit\ContextCreatorTrait;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use stdClass;

#[CoversClass(ObjectEncoder::class), CoversClass(ObjectAccess::class)]
final class ObjectEncoderNullElementTest extends TestCase
{
    use ContextCreatorTrait;

    private const SCHEMA = <<<'XML'
    <complexType name="optionalType">
        <sequence>
            <element name="first" type="xsd:string" minOccurs="0"/>
            <element name="second" type="xsd:string" minOccurs="0"/>
        </sequence>
        <attribute name="attribute" type="xsd:string"/>
    </complexType>
    <complexType name="parentType">
        <sequence>
            <element name="child" type="tns:optionalType"/>
            <element name="last" type="xsd:string" minOccurs="0"/>
        </sequence>
    </complexType>
    <complexType name="mixedType">
        <sequence>
            <element name="optional" type="xsd:string" minOccurs="0"/>
            <element name="optionalList" type="xsd:string" minOccurs="0" maxOccurs="unbounded"/>
            <element name="optionalNillable" type="xsd:string" minOccurs="0" nillable="true"/>
            <element name="requiredNillable" type="xsd:string" nillable="true"/>
            <element name="required" type="xsd:string"/>
            <element name="custom" type="tns:customType" minOccurs="0"/>
            <element name="nullWriting" type="tns:nullWritingType" minOccurs="0"/>
        </sequence>
        <attribute name="attribute" type="xsd:string"/>
    </complexType>
    <complexType name="textType">
        <simpleContent>
            <extension base="xsd:string">
                <attribute name="attribute" type="xsd:string"/>
            </extension>
        </simpleContent>
    </complexType>
    <complexType name="customType">
        <simpleContent><extension base="xsd:string"/></simpleContent>
    </complexType>
    <complexType name="nullWritingType">
        <simpleContent><extension base="xsd:string"/></simpleContent>
    </complexType>
    XML;

    /** @return iterable<string, array{string, object, string}> */
    public static function encoded_values(): iterable
    {
        $optional = static fn (array $data): object => (object) [...['attribute' => null, 'first' => null, 'second' => null], ...$data];

        yield 'all optional elements null' => ['optionalType', $optional([]), '<optionalType/>'];
        yield 'all optional elements absent' => ['optionalType', (object) [], '<optionalType/>'];
        yield 'attribute and all optional elements null' => [
            'optionalType',
            $optional(['attribute' => 'a']),
            '<optionalType attribute="a"/>',
        ];
        yield 'first optional element null' => [
            'optionalType',
            $optional(['second' => 's']),
            '<optionalType><second>s</second></optionalType>',
        ];
        yield 'last optional element null' => [
            'optionalType',
            $optional(['first' => 'f']),
            '<optionalType><first>f</first></optionalType>',
        ];
        yield 'nested object with all optional elements null' => [
            'parentType',
            (object) ['child' => $optional([]), 'last' => null],
            '<parentType><child/></parentType>',
        ];
        yield 'mixed null elements' => [
            'mixedType',
            (object) [
                'attribute' => null,
                'optional' => null,
                'optionalList' => null,
                'optionalNillable' => null,
                'requiredNillable' => null,
                'required' => 'r',
                'custom' => null,
                'nullWriting' => null,
            ],
            '<mixedType><optionalNillable xsi:nil="true" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>'
                . '<requiredNillable xsi:nil="true" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>'
                . '<required>r</required><custom>none</custom></mixedType>',
        ];
    }

    /**
     * An empty element keeps its separate end tag when its null optional elements write nothing.
     */
    #[Test, DataProvider('encoded_values')]
    public function null_optional_elements_encode_byte_for_byte(string $typeName, object $data, string $expected): void
    {
        $actual = (new ObjectEncoder(stdClass::class))->iso(self::context($typeName))->to($data);

        static::assertSame($expected, $actual);
    }

    #[Test]
    public function only_default_optional_non_nillable_elements_write_nothing_for_null(): void
    {
        static::assertSame(
            [
                'attribute' => false,
                'optional' => true,
                'optionalList' => false,
                'optionalNillable' => false,
                'requiredNillable' => false,
                'required' => false,
                'custom' => true,
                'nullWriting' => false,
            ],
            ObjectAccess::forContext(self::context('mixedType'))->nullWritesNothing
        );
        static::assertSame(
            ['attribute' => false, '_' => false],
            ObjectAccess::forContext(self::context('textType'))->nullWritesNothing
        );
    }

    private static function context(string $typeName): Context
    {
        $metadata = self::createMetadataFromWsdl(self::SCHEMA, 'type="tns:'.$typeName.'"');
        $registry = EncoderRegistry::default()
            ->addComplexTypeConverter('https://test', 'customType', new ThrowingLensEncoder())
            ->addComplexTypeConverter('https://test', 'nullWritingType', new NullWritingEncoder());

        return new Context(
            $metadata->getTypes()->fetchByNameAndXmlNamespace($typeName, 'https://test')->getXsdType(),
            $metadata,
            $registry,
            self::buildNamespaces(),
            BindingUse::LITERAL,
        );
    }
}
