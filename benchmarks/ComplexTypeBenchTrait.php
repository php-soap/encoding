<?php

declare(strict_types=1);

namespace Soap\Encoding\Benchmarks;

use Soap\Encoding\Driver;
use Soap\Encoding\EncoderRegistry;
use Soap\Engine\HttpBinding\SoapResponse;
use Soap\Wsdl\Loader\CallbackLoader;
use Soap\WsdlReader\Metadata\Wsdl1MetadataProvider;
use Soap\WsdlReader\Wsdl1Reader;

/**
 * Shared setup for encode/decode benchmarks.
 * Complex type: itemType (9 properties including nested addressType).
 */
trait ComplexTypeBenchTrait
{
    protected Driver $literalDriver;
    protected Driver $encodedDriver;
    protected object $singleItem;
    /** @var list<object> */
    protected array $items;
    protected SoapResponse $literalResponse;
    protected SoapResponse $encodedResponse;

    protected BenchSoapClient $extSoapLiteral;
    protected BenchSoapClient $extSoapEncoded;
    protected string $minimalResponse;
    protected string $fullResponse;

    public function setUp(): void
    {
        $this->singleItem = (object) [
            'id' => 42,
            'name' => 'Widget Pro',
            'description' => 'A high-quality widget for professional use',
            'price' => 29.99,
            'quantity' => 100,
            'active' => true,
            'sku' => 'WDG-PRO-001',
            'category' => 'Electronics',
            'address' => (object) [
                'street' => '123 Main St',
                'city' => 'Springfield',
                'zip' => '62701',
            ],
        ];

        $this->items = [];
        for ($i = 0; $i < 500; $i++) {
            $this->items[] = (object) [
                'id' => $i,
                'name' => 'Widget ' . $i,
                'description' => 'Description for widget ' . $i,
                'price' => 9.99 + $i,
                'quantity' => $i * 10,
                'active' => $i % 2 === 0,
                'sku' => 'WDG-' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'category' => 'Category ' . ($i % 10),
                'address' => (object) [
                    'street' => $i . ' Elm St',
                    'city' => 'City ' . ($i % 50),
                    'zip' => str_pad((string) ($i % 99999), 5, '0', STR_PAD_LEFT),
                ],
            ];
        }

        // php-soap/encoding
        $this->literalDriver = $this->buildPhpSoapDriver('literal');
        $this->encodedDriver = $this->buildPhpSoapDriver('encoded');

        $this->literalResponse = new SoapResponse(
            $this->literalDriver->encode('test', [$this->singleItem])->getRequest()
        );
        $this->encodedResponse = new SoapResponse(
            $this->encodedDriver->encode('test', [$this->singleItem])->getRequest()
        );

        // ext-soap
        $this->extSoapLiteral = $this->buildExtSoapClient('literal');
        $this->extSoapEncoded = $this->buildExtSoapClient('encoded');

        $this->minimalResponse = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<SOAP-ENV:Body><ns1:testResponse xmlns:ns1="http://test-uri/"/>'
            . '</SOAP-ENV:Body></SOAP-ENV:Envelope>';

        $this->fullResponse = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<SOAP-ENV:Body><ns1:testResponse xmlns:ns1="http://test-uri/">'
            . '<testParam><id>42</id><name>Widget Pro</name>'
            . '<description>A high-quality widget for professional use</description>'
            . '<price>29.99</price><quantity>100</quantity><active>true</active>'
            . '<sku>WDG-PRO-001</sku><category>Electronics</category>'
            . '<address><street>123 Main St</street><city>Springfield</city><zip>62701</zip></address>'
            . '</testParam></ns1:testResponse>'
            . '</SOAP-ENV:Body></SOAP-ENV:Envelope>';

        // Warmup ext-soap
        $this->extSoapLiteral->mockResponse = $this->minimalResponse;
        $this->extSoapLiteral->__soapCall('test', ['testParam' => $this->singleItem]);
        $this->extSoapEncoded->mockResponse = $this->minimalResponse;
        $this->extSoapEncoded->__soapCall('test', ['testParam' => $this->singleItem]);
    }

    private function buildPhpSoapDriver(string $use): Driver
    {
        $encodingStyle = $use === 'encoded'
            ? ' encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"'
            : '';

        $wsdl = <<<WSDL
        <definitions name="BenchTest"
            xmlns:xsd="http://www.w3.org/2001/XMLSchema"
            xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
            xmlns:tns="http://test-uri/"
            xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
            xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"
            xmlns="http://schemas.xmlsoap.org/wsdl/"
            targetNamespace="http://test-uri/">
          <types>
          <schema xmlns="http://www.w3.org/2001/XMLSchema" targetNamespace="http://test-uri/">
           <xsd:import namespace="http://schemas.xmlsoap.org/soap/encoding/" />
           <xsd:import namespace="http://schemas.xmlsoap.org/wsdl/" />
            <complexType name="addressType">
                <sequence>
                    <element name="street" type="xsd:string"/>
                    <element name="city" type="xsd:string"/>
                    <element name="zip" type="xsd:string"/>
                </sequence>
            </complexType>
            <complexType name="itemType">
                <sequence>
                    <element name="id" type="xsd:int"/>
                    <element name="name" type="xsd:string"/>
                    <element name="description" type="xsd:string"/>
                    <element name="price" type="xsd:float"/>
                    <element name="quantity" type="xsd:int"/>
                    <element name="active" type="xsd:boolean"/>
                    <element name="sku" type="xsd:string"/>
                    <element name="category" type="xsd:string"/>
                    <element name="address" type="tns:addressType"/>
                </sequence>
            </complexType>
          </schema>
          </types>
          <message name="testMessage">
            <part name="testParam" type="tns:itemType"/>
          </message>
          <portType name="testPortType">
            <operation name="test">
              <input message="testMessage"/>
              <output message="testMessage"/>
            </operation>
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
            <port name="testPort" binding="tns:testBinding">
              <soap:address location="test://"/>
            </port>
          </service>
        </definitions>
        WSDL;

        $wsdlObject = (new Wsdl1Reader(
            new CallbackLoader(static fn (): string => $wsdl)
        ))('file.wsdl');

        $registry = EncoderRegistry::default();
        $metadata = (new Wsdl1MetadataProvider($wsdlObject))->getMetadata();

        return Driver::createFromMetadata($metadata, $wsdlObject->namespaces, $registry);
    }

    private function buildExtSoapClient(string $use): BenchSoapClient
    {
        $encodingStyle = $use === 'encoded'
            ? ' encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"'
            : '';

        $wsdl = <<<WSDL
<?xml version="1.0" encoding="UTF-8"?>
<definitions name="BenchTest"
    xmlns:xsd="http://www.w3.org/2001/XMLSchema"
    xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
    xmlns:tns="http://test-uri/"
    xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
    xmlns="http://schemas.xmlsoap.org/wsdl/"
    targetNamespace="http://test-uri/">
  <types>
    <xsd:schema targetNamespace="http://test-uri/">
      <xsd:import namespace="http://schemas.xmlsoap.org/soap/encoding/" />
      <xsd:complexType name="addressType">
        <xsd:sequence>
          <xsd:element name="street" type="xsd:string"/>
          <xsd:element name="city" type="xsd:string"/>
          <xsd:element name="zip" type="xsd:string"/>
        </xsd:sequence>
      </xsd:complexType>
      <xsd:complexType name="itemType">
        <xsd:sequence>
          <xsd:element name="id" type="xsd:int"/>
          <xsd:element name="name" type="xsd:string"/>
          <xsd:element name="description" type="xsd:string"/>
          <xsd:element name="price" type="xsd:float"/>
          <xsd:element name="quantity" type="xsd:int"/>
          <xsd:element name="active" type="xsd:boolean"/>
          <xsd:element name="sku" type="xsd:string"/>
          <xsd:element name="category" type="xsd:string"/>
          <xsd:element name="address" type="tns:addressType"/>
        </xsd:sequence>
      </xsd:complexType>
    </xsd:schema>
  </types>
  <message name="testMessage"><part name="testParam" type="tns:itemType"/></message>
  <portType name="testPortType">
    <operation name="test"><input message="tns:testMessage"/><output message="tns:testMessage"/></operation>
  </portType>
  <binding name="testBinding" type="tns:testPortType">
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

        $wsdlFile = tempnam(sys_get_temp_dir(), 'wsdl_') . '.wsdl';
        file_put_contents($wsdlFile, $wsdl);

        $client = new BenchSoapClient($wsdlFile, [
            'trace' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
        ]);

        unlink($wsdlFile);

        return $client;
    }
}
