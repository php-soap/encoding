<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\PhpCompatibility;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Driver;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\EncoderRegistry;
use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Reader\OperationReader;
use Soap\Wsdl\Loader\CallbackLoader;
use Soap\WsdlReader\Metadata\Wsdl1MetadataProvider;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Wsdl1Reader;
use VeeWee\Xml\Dom\Document;
use VeeWee\Xml\Exception\RuntimeException;
use function Psl\Iter\first;
use function Psl\Vec\map;
use function VeeWee\Xml\Dom\Configurator\comparable;

abstract class AbstractCompatibilityTests extends TestCase
{
    protected string $schema;
    protected string $type;
    protected mixed $param;
    protected string $style = "rpc";
    protected string $use = "encoded";
    protected string $attributeFormDefault = '';
    protected int $features = 0;

    abstract protected function expectXml(): string;

    protected function calculateParam(): mixed
    {
        return $this->param;
    }

    protected function expectDecoded(): mixed
    {
        return $this->calculateParam();
    }

    protected function registry(): EncoderRegistry
    {
        return EncoderRegistry::default();
    }

    #[Test]
    public function it_is_compatible_with_phps_encoding()
    {
        $wsdl  = <<<EOF
  <definitions name="InteropTest"
      xmlns:xsd="http://www.w3.org/2001/XMLSchema"
      xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
      xmlns:tns="http://test-uri/"
      xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
      xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"
      xmlns="http://schemas.xmlsoap.org/wsdl/"
      targetNamespace="http://test-uri/"
      >
    <types>
    <schema xmlns="http://www.w3.org/2001/XMLSchema" targetNamespace="http://test-uri/" $this->attributeFormDefault>
     <xsd:import namespace="http://schemas.xmlsoap.org/soap/encoding/" />
     <xsd:import namespace="http://schemas.xmlsoap.org/wsdl/" />
      $this->schema
    </schema>
    </types>
    <message name="testMessage">
      <part name="testParam" $this->type/>
    </message>
      <portType name="testPortType">
          <operation name="test">
              <input message="testMessage"/>
          </operation>
      </portType>
      <binding name="testBinding" type="testPortType">
          <soap:binding style="rpc" transport="http://schemas.xmlsoap.org/soap/http"/>
          <operation name="test">
              <soap:operation soapAction="#test" style="$this->style"/>
              <input>
                  <soap:body use="$this->use" namespace="http://test-uri/" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
              </input>
          </operation>
      </binding>
      <service name="testService">
     <port name="testPort" binding="tns:testBinding">
       <soap:address location="test://" />
     </port>
   </service>
  </definitions>
  EOF;

        $wsdlObject = (new Wsdl1Reader(
            new CallbackLoader(static fn (): string => $wsdl)
        ))('file.wsdl');
        $registry = $this->registry();
        $metadataProvider = new Wsdl1MetadataProvider($wsdlObject);
        $metadata = $metadataProvider->getMetadata();
        $driver = Driver::createFromMetadata($metadata, $wsdlObject->namespaces, $registry);

        $encoded = $driver->encode('test', [$this->calculateParam()]);
        $request = $encoded->getRequest();
        try {
            static::assertSame(
                Document::fromXmlString($this->expectXml(), comparable())->toXmlString(),
                Document::fromXmlString($request, comparable())->toXmlString()
            );
        } catch (RuntimeException $e) {
            static::fail('Invalid XML: ' . $e->getMessage() . PHP_EOL . $request);
        }

        $method = $metadata->getMethods()->fetchByName('test');
        $param = first($method->getParameters());
        $decodeContext = new Context(
            $param->getType(),
            $metadata,
            $registry,
            $wsdlObject->namespaces,
            $method->getMeta()
                ->inputBindingUsage()
                ->map(BindingUse::from(...))
                ->unwrapOr(BindingUse::LITERAL)
        );

        $decoder = $registry->detectEncoderForContext($decodeContext);
        $params = (new OperationReader($method->getMeta()))($request);
        $paramXml = implode('', map($params->elements(), static fn (Element $element): string => $element->value()));
        $decoded = $decoder->iso($decodeContext)->from($paramXml);

        static::assertEquals($this->expectDecoded(), $decoded);
    }
}
