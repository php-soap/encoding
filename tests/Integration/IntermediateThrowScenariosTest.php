<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Integration;

use Closure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Driver;
use Soap\Encoding\EncoderRegistry;
use Soap\Encoding\Test\Fixture\Model\Address;
use Soap\Encoding\Test\Fixture\Model\Item;
use Soap\Engine\HttpBinding\SoapResponse;
use Soap\Wsdl\Loader\CallbackLoader;
use Soap\WsdlReader\Metadata\Wsdl1MetadataProvider;
use Soap\WsdlReader\Wsdl1Reader;

/**
 * Encodes and decodes that succeed while exceptions are thrown and caught internally along the way:
 * stdClass hydration, absent elements and attributes, absent and uninitialised properties on encode,
 * untyped scalar values and soap-enc arrays of a builtin item type.
 */
#[CoversClass(Driver::class)]
final class IntermediateThrowScenariosTest extends TestCase
{
    private const ITEM_SCHEMA = <<<XSD
        <complexType name="addressType"><sequence>
            <element name="street" type="xsd:string" minOccurs="0"/>
            <element name="city" type="xsd:string" minOccurs="0"/>
            <element name="zip" type="xsd:string" minOccurs="0" nillable="true"/>
        </sequence></complexType>
        <complexType name="itemType">
            <sequence>
                <element name="id" type="xsd:int"/>
                <element name="name" type="xsd:string" minOccurs="0"/>
                <element name="price" type="xsd:float" minOccurs="0"/>
                <element name="active" type="xsd:boolean" minOccurs="0"/>
                <element name="address" type="tns:addressType" minOccurs="0" nillable="true"/>
                <element name="tags" type="xsd:string" minOccurs="0" maxOccurs="unbounded"/>
            </sequence>
            <attribute name="code" type="xsd:string"/>
            <attribute name="rank" type="xsd:int" use="optional"/>
        </complexType>
        <complexType name="intArray"><complexContent><restriction base="SOAP-ENC:Array">
            <attribute ref="SOAP-ENC:arrayType" wsdl:arrayType="int[]"/>
        </restriction></complexContent></complexType>
        <complexType name="untypedValues"><sequence>
            <element name="integer"/>
            <element name="float"/>
            <element name="boolean"/>
            <element name="string"/>
        </sequence></complexType>
        XSD;

    /**
     * @param Closure(): mixed $operation
     */
    #[DataProvider('provideScenarios')]
    public function test_it_encodes_and_decodes_the_scenario(Closure $operation, mixed $expected): void
    {
        static::assertEquals($expected, $operation());
    }

    /**
     * @return iterable<string, array{operation: Closure(): mixed, expected: mixed}>
     */
    public static function provideScenarios(): iterable
    {
        $literal = self::driver('tns:itemType', 'literal');
        $typed = self::driver(
            'tns:itemType',
            'literal',
            EncoderRegistry::default()
                ->addClassMap('http://test-uri/', 'itemType', Item::class)
                ->addClassMap('http://test-uri/', 'addressType', Address::class),
        );

        yield 'decode sparse response into stdClass' => [
            'operation' => static fn (): mixed => $literal->decode(
                'test',
                self::response('<testParam><id>42</id><address><city>C</city></address></testParam>'),
            ),
            'expected' => (object) [
                'code' => null,
                'rank' => null,
                'id' => 42,
                'name' => null,
                'price' => null,
                'active' => null,
                'address' => (object) ['street' => null, 'city' => 'C', 'zip' => null],
                'tags' => [],
            ],
        ];

        yield 'decode nil element into stdClass' => [
            'operation' => static fn (): mixed => $literal->decode(
                'test',
                self::response('<testParam><id>42</id><address xsi:nil="true"/></testParam>'),
            ),
            'expected' => (object) [
                'code' => null,
                'rank' => null,
                'id' => 42,
                'name' => null,
                'price' => null,
                'active' => null,
                'address' => null,
                'tags' => [],
            ],
        ];

        yield 'decode single occurrence of a repeating element' => [
            'operation' => static fn (): mixed => $literal->decode(
                'test',
                self::response('<testParam><id>1</id><tags>a</tags></testParam>'),
            ),
            'expected' => (object) [
                'code' => null,
                'rank' => null,
                'id' => 1,
                'name' => null,
                'price' => null,
                'active' => null,
                'address' => null,
                'tags' => ['a'],
            ],
        ];

        yield 'encode stdClass with absent properties' => [
            'operation' => static fn (): string => $literal->encode('test', [(object) ['id' => 1]])->getRequest(),
            'expected' => self::literalRequest(
                '<testParam><id>1</id><address xsi:nil="true" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/></testParam>'
            ),
        ];

        yield 'encode sparse array input' => [
            'operation' => static fn (): string => $literal->encode('test', [['id' => 1, 'address' => ['city' => 'C']]])->getRequest(),
            'expected' => self::literalRequest(
                '<testParam><id>1</id><address><city>C</city><zip xsi:nil="true" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/></address></testParam>'
            ),
        ];

        yield 'encode uninitialised typed properties' => [
            'operation' => static function () use ($typed): string {
                $item = new Item();
                $item->id = 42;

                return $typed->encode('test', [$item])->getRequest();
            },
            'expected' => self::literalRequest(
                '<testParam><id>42</id><address xsi:nil="true" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/></testParam>'
            ),
        ];

        $untyped = self::driver('tns:untypedValues', 'literal');
        yield 'decode untyped values' => [
            'operation' => static fn (): mixed => $untyped->decode(
                'test',
                self::response('<testParam><integer>5</integer><float>1.5</float><boolean>true</boolean><string>word</string></testParam>'),
            ),
            'expected' => (object) ['integer' => 5, 'float' => 1.5, 'boolean' => true, 'string' => 'word'],
        ];

        $soapEncArray = self::driver('tns:intArray', 'encoded');
        yield 'decode soap-enc array of a builtin type' => [
            'operation' => static fn (): mixed => $soapEncArray->decode(
                'test',
                self::response('<testParam SOAP-ENC:arrayType="xsd:int[2]"><item>1</item><item>2</item></testParam>'),
            ),
            'expected' => [1, 2],
        ];
    }

    private static function driver(string $partType, string $use, ?EncoderRegistry $registry = null): Driver
    {
        $schema = self::ITEM_SCHEMA;
        $encodingStyle = $use === 'encoded' ? ' encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"' : '';
        $wsdl = <<<WSDL
            <definitions name="Scenarios"
                xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
                xmlns:tns="http://test-uri/"
                xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
                xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"
                xmlns="http://schemas.xmlsoap.org/wsdl/"
                targetNamespace="http://test-uri/">
                <types>
                    <schema xmlns="http://www.w3.org/2001/XMLSchema" targetNamespace="http://test-uri/">
                        <xsd:import namespace="http://schemas.xmlsoap.org/soap/encoding/"/>
                        <xsd:import namespace="http://schemas.xmlsoap.org/wsdl/"/>
                        {$schema}
                    </schema>
                </types>
                <message name="testMessage"><part name="testParam" type="{$partType}"/></message>
                <portType name="testPortType">
                    <operation name="test"><input message="testMessage"/><output message="testMessage"/></operation>
                </portType>
                <binding name="testBinding" type="testPortType">
                    <soap:binding style="rpc" transport="http://schemas.xmlsoap.org/soap/http"/>
                    <operation name="test">
                        <soap:operation soapAction="#test" style="rpc"/>
                        <input><soap:body use="{$use}" namespace="http://test-uri/"{$encodingStyle}/></input>
                        <output><soap:body use="{$use}" namespace="http://test-uri/"{$encodingStyle}/></output>
                    </operation>
                </binding>
                <service name="testService">
                    <port name="testPort" binding="tns:testBinding"><soap:address location="test://"/></port>
                </service>
            </definitions>
            WSDL;

        $wsdlObject = (new Wsdl1Reader(new CallbackLoader(static fn (): string => $wsdl)))('file.wsdl');

        return Driver::createFromMetadata(
            (new Wsdl1MetadataProvider($wsdlObject))->getMetadata(),
            $wsdlObject->namespaces,
            $registry ?? EncoderRegistry::default(),
        );
    }

    private static function response(string $testParam): SoapResponse
    {
        return new SoapResponse(
            '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"'
            .' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"'
            .' xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/">'
            .'<SOAP-ENV:Body><ns1:testResponse xmlns:ns1="http://test-uri/">'.$testParam.'</ns1:testResponse></SOAP-ENV:Body>'
            .'</SOAP-ENV:Envelope>'
        );
    }

    private static function literalRequest(string $testParam): string
    {
        return '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">'
            .'<SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">'
            .'<tns:test xmlns:tns="http://test-uri/">'.$testParam.'</tns:test>'
            .'</SOAP-ENV:Body></SOAP-ENV:Envelope>'."\n";
    }
}
