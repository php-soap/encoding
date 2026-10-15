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
 * untyped scalar values and the soap-enc arrays, structs and apache maps of SOAP 1.1 and SOAP 1.2 encoding.
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
        <complexType name="addressArray"><complexContent><restriction base="SOAP-ENC:Array">
            <attribute ref="SOAP-ENC:arrayType" wsdl:arrayType="tns:addressType[]"/>
        </restriction></complexContent></complexType>
        <complexType name="intArrayArray"><complexContent><restriction base="SOAP-ENC:Array">
            <attribute ref="SOAP-ENC:arrayType" wsdl:arrayType="tns:intArray[]"/>
        </restriction></complexContent></complexType>
        <complexType name="anyTypeArray"><complexContent><restriction base="SOAP-ENC:Array">
            <attribute ref="SOAP-ENC:arrayType" wsdl:arrayType="xsd:anyType[]"/>
        </restriction></complexContent></complexType>
        <complexType name="soap12IntArray"><complexContent>
            <restriction base="enc12:Array" xmlns:enc12="http://www.w3.org/2003/05/soap-encoding">
                <attribute ref="enc12:itemType" wsdl:itemType="xsd:int"/>
                <attribute ref="enc12:arraySize" wsdl:arraySize="*"/>
            </restriction>
        </complexContent></complexType>
        <complexType name="soap12AddressArray"><complexContent>
            <restriction base="enc12:Array" xmlns:enc12="http://www.w3.org/2003/05/soap-encoding">
                <attribute ref="enc12:itemType" wsdl:itemType="tns:addressType"/>
                <attribute ref="enc12:arraySize" wsdl:arraySize="*"/>
            </restriction>
        </complexContent></complexType>
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

        yield 'encode object of a class that does not declare the properties' => [
            'operation' => static fn (): string => $literal->encode('test', [new class {
                public int $id = 1;
            }])->getRequest(),
            'expected' => self::literalRequest(
                '<testParam><id>1</id><address xsi:nil="true" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/></testParam>'
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

        $addressArray = self::driver('tns:addressArray', 'encoded');
        yield 'decode soap-enc array of a complex type' => [
            'operation' => static fn (): mixed => $addressArray->decode(
                'test',
                self::response(
                    '<testParam SOAP-ENC:arrayType="tns:addressType[2]">'
                    .'<item><street>S</street><city>C</city></item><item xsi:nil="true"/>'
                    .'</testParam>'
                ),
            ),
            'expected' => [
                (object) ['street' => 'S', 'city' => 'C', 'zip' => null],
                (object) ['street' => null, 'city' => null, 'zip' => null],
            ],
        ];

        yield 'encode soap-enc array of a complex type' => [
            'operation' => static fn (): string => $addressArray->encode('test', [[(object) ['city' => 'C', 'zip' => '1000']]])->getRequest(),
            'expected' => self::encodedRequest(
                '<testParam xmlns:tns="http://test-uri/" xsi:type="tns:addressArray" SOAP-ENC:arrayType="tns:addressType[1]" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
                .'<item xmlns:tns="http://test-uri/" xsi:type="tns:addressType" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
                .'<city xmlns:xsd="http://www.w3.org/2001/XMLSchema" xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">C</city>'
                .'<zip xmlns:xsd="http://www.w3.org/2001/XMLSchema" xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">1000</zip>'
                .'</item></testParam>'
            ),
        ];

        $arrayOfArrays = self::driver('tns:intArrayArray', 'encoded');
        yield 'decode soap-enc array of arrays' => [
            'operation' => static fn (): mixed => $arrayOfArrays->decode(
                'test',
                self::response(
                    '<testParam SOAP-ENC:arrayType="tns:intArray[2]">'
                    .'<item SOAP-ENC:arrayType="xsd:int[2]"><item>1</item><item>2</item></item>'
                    .'<item SOAP-ENC:arrayType="xsd:int[0]"/>'
                    .'</testParam>'
                ),
            ),
            'expected' => [[1, 2], []],
        ];

        yield 'encode soap-enc array of arrays' => [
            'operation' => static fn (): string => $arrayOfArrays->encode('test', [[[1], []]])->getRequest(),
            'expected' => self::encodedRequest(
                '<testParam xmlns:tns="http://test-uri/" xsi:type="tns:intArrayArray" SOAP-ENC:arrayType="tns:intArray[2]" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
                .'<item xmlns:tns="http://test-uri/" xsi:type="tns:intArray" SOAP-ENC:arrayType="xsd:int[1]" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
                .'<item xmlns:xsd="http://www.w3.org/2001/XMLSchema" xsi:type="xsd:int" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">1</item>'
                .'</item>'
                .'<item xmlns:tns="http://test-uri/" xsi:type="tns:intArray" SOAP-ENC:arrayType="xsd:int[0]" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>'
                .'</testParam>'
            ),
        ];

        yield 'decode soap-enc array with nil items' => [
            'operation' => static fn (): mixed => $soapEncArray->decode(
                'test',
                self::response('<testParam SOAP-ENC:arrayType="xsd:int[2]"><item>1</item><item xsi:nil="true"/></testParam>'),
            ),
            'expected' => [1, null],
        ];

        yield 'encode empty soap-enc array' => [
            'operation' => static fn (): string => $soapEncArray->encode('test', [[]])->getRequest(),
            'expected' => self::encodedRequest(
                '<testParam xmlns:tns="http://test-uri/" xsi:type="tns:intArray" SOAP-ENC:arrayType="xsd:int[0]" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>'
            ),
        ];

        $anyTypeArray = self::driver('tns:anyTypeArray', 'encoded');
        yield 'decode soap-enc array with an xsi:type per item' => [
            'operation' => static fn (): mixed => $anyTypeArray->decode(
                'test',
                self::response(
                    '<testParam SOAP-ENC:arrayType="xsd:anyType[4]">'
                    .'<item xsi:type="xsd:int">1</item><item xsi:type="xsd:string">a</item>'
                    .'<item xsi:type="xsd:float">1.5</item><item>true</item>'
                    .'</testParam>'
                ),
            ),
            'expected' => [1, 'a', 1.5, true],
        ];

        yield 'encode soap-enc array with an xsi:type per item' => [
            'operation' => static fn (): string => $anyTypeArray->encode('test', [[1, 'a']])->getRequest(),
            'expected' => self::encodedRequest(
                '<testParam xmlns:tns="http://test-uri/" xsi:type="tns:anyTypeArray" SOAP-ENC:arrayType="xsd:anyType[2]" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
                .'<item xmlns:xsd="http://www.w3.org/2001/XMLSchema" xsi:type="xsd:int" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">1</item>'
                .'<item xmlns:xsd="http://www.w3.org/2001/XMLSchema" xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">a</item>'
                .'</testParam>'
            ),
        ];

        $apacheMap = self::driver('apache:Map', 'encoded');
        yield 'decode apache map' => [
            'operation' => static fn (): mixed => $apacheMap->decode(
                'test',
                self::response(
                    '<testParam xsi:type="apache:Map">'
                    .'<item><key xsi:type="xsd:string">a</key><value xsi:type="xsd:int">1</value></item>'
                    .'<item><key>b</key><value>x</value></item>'
                    .'</testParam>'
                ),
            ),
            'expected' => ['a' => 1, 'b' => 'x'],
        ];

        yield 'encode apache map' => [
            'operation' => static fn (): string => $apacheMap->encode('test', [['a' => 1]])->getRequest(),
            'expected' => self::encodedRequest(
                '<testParam xmlns:apache="http://xml.apache.org/xml-soap" xsi:type="apache:Map" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><item>'
                .'<key xmlns:xsd="http://www.w3.org/2001/XMLSchema" xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">a</key>'
                .'<value xmlns:xsd="http://www.w3.org/2001/XMLSchema" xsi:type="xsd:int" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">1</value>'
                .'</item></testParam>'
            ),
        ];

        $struct = self::driver('SOAP-ENC:Struct', 'encoded');
        yield 'decode soap-enc struct' => [
            'operation' => static fn (): mixed => $struct->decode(
                'test',
                self::response('<testParam xsi:type="SOAP-ENC:Struct"><a xsi:type="xsd:int">1</a><b>x</b></testParam>'),
            ),
            'expected' => (object) ['a' => 1, 'b' => 'x'],
        ];

        yield 'encode soap-enc struct' => [
            'operation' => static fn (): string => $struct->encode('test', [(object) ['a' => 1]])->getRequest(),
            'expected' => self::encodedRequest(
                '<testParam xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" xsi:type="SOAP-ENC:Struct" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
                .'<a xmlns:xsd="http://www.w3.org/2001/XMLSchema" xsi:type="xsd:int" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">1</a>'
                .'</testParam>'
            ),
        ];

        $soap12Array = self::driver('tns:soap12IntArray', 'encoded', soap12: true);
        yield 'decode soap 1.2 encoded array' => [
            'operation' => static fn (): mixed => $soap12Array->decode(
                'test',
                self::response('<testParam SOAP-ENC:itemType="xsd:int" SOAP-ENC:arraySize="2"><item>1</item><item>2</item></testParam>', soap12: true),
            ),
            'expected' => [1, 2],
        ];

        yield 'encode soap 1.2 encoded array' => [
            'operation' => static fn (): string => $soap12Array->encode('test', [[1]])->getRequest(),
            'expected' => self::encodedRequest(
                '<testParam xmlns:tns="http://test-uri/" xsi:type="tns:soap12IntArray" SOAP-ENC:arrayType="xsd:int[1]" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
                .'<item xmlns:xsd="http://www.w3.org/2001/XMLSchema" xsi:type="xsd:int" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">1</item>'
                .'</testParam>',
                soap12: true,
            ),
        ];

        $soap12AddressArray = self::driver('tns:soap12AddressArray', 'encoded', soap12: true);
        yield 'decode soap 1.2 encoded array of a complex type' => [
            'operation' => static fn (): mixed => $soap12AddressArray->decode(
                'test',
                self::response(
                    '<testParam SOAP-ENC:itemType="tns:addressType" SOAP-ENC:arraySize="1"><item><city>C</city></item></testParam>',
                    soap12: true,
                ),
            ),
            'expected' => [(object) ['street' => null, 'city' => 'C', 'zip' => null]],
        ];

        $soap12Literal = self::driver('tns:itemType', 'literal', soap12: true);
        yield 'decode soap 1.2 literal envelope' => [
            'operation' => static fn (): mixed => $soap12Literal->decode(
                'test',
                self::response('<testParam><id>42</id></testParam>', soap12: true),
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

        yield 'encode soap 1.2 literal envelope' => [
            'operation' => static fn (): string => $soap12Literal->encode('test', [(object) ['id' => 1]])->getRequest(),
            'expected' => self::literalRequest(
                '<testParam><id>1</id><address xsi:nil="true" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/></testParam>',
                soap12: true,
            ),
        ];
    }

    private static function driver(string $partType, string $use, ?EncoderRegistry $registry = null, bool $soap12 = false): Driver
    {
        $schema = self::ITEM_SCHEMA;
        $encodingStyle = $use === 'encoded' ? ' encodingStyle="'.self::encodingNamespace($soap12).'"' : '';
        $bindingNamespace = $soap12 ? 'http://schemas.xmlsoap.org/wsdl/soap12/' : 'http://schemas.xmlsoap.org/wsdl/soap/';
        $wsdl = <<<WSDL
            <definitions name="Scenarios"
                xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
                xmlns:apache="http://xml.apache.org/xml-soap"
                xmlns:tns="http://test-uri/"
                xmlns:soap="{$bindingNamespace}"
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

    private static function response(string $testParam, bool $soap12 = false): SoapResponse
    {
        return new SoapResponse(
            '<SOAP-ENV:Envelope xmlns:SOAP-ENV="'.self::envelopeNamespace($soap12).'"'
            .' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"'
            .' xmlns:SOAP-ENC="'.self::encodingNamespace($soap12).'"'
            .' xmlns:apache="http://xml.apache.org/xml-soap" xmlns:tns="http://test-uri/">'
            .'<SOAP-ENV:Body><ns1:testResponse xmlns:ns1="http://test-uri/">'.$testParam.'</ns1:testResponse></SOAP-ENV:Body>'
            .'</SOAP-ENV:Envelope>'
        );
    }

    private static function literalRequest(string $testParam, bool $soap12 = false): string
    {
        $envelope = self::envelopeNamespace($soap12);

        return '<SOAP-ENV:Envelope xmlns:SOAP-ENV="'.$envelope.'">'
            .'<SOAP-ENV:Body xmlns:SOAP-ENV="'.$envelope.'">'
            .'<tns:test xmlns:tns="http://test-uri/">'.$testParam.'</tns:test>'
            .'</SOAP-ENV:Body></SOAP-ENV:Envelope>'."\n";
    }

    private static function encodedRequest(string $testParam, bool $soap12 = false): string
    {
        $envelope = self::envelopeNamespace($soap12);
        $encoding = self::encodingNamespace($soap12);

        return '<SOAP-ENV:Envelope xmlns:SOAP-ENV="'.$envelope.'">'
            .'<SOAP-ENV:Body xmlns:SOAP-ENC="'.$encoding.'" SOAP-ENV:encodingStyle="'.$encoding.'" xmlns:SOAP-ENV="'.$envelope.'">'
            .'<tns:test xmlns:tns="http://test-uri/">'.$testParam.'</tns:test>'
            .'</SOAP-ENV:Body></SOAP-ENV:Envelope>'."\n";
    }

    private static function envelopeNamespace(bool $soap12): string
    {
        return $soap12 ? 'http://www.w3.org/2003/05/soap-envelope' : 'http://schemas.xmlsoap.org/soap/envelope/';
    }

    private static function encodingNamespace(bool $soap12): string
    {
        return $soap12 ? 'http://www.w3.org/2003/05/soap-encoding' : 'http://schemas.xmlsoap.org/soap/encoding/';
    }
}
