<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Soap\Encoding\Driver;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\EncoderRegistry;
use Soap\Encoding\Xml\Reader\SoapEnvelopeReader;
use Soap\Wsdl\Loader\CallbackLoader;
use Soap\WsdlReader\Metadata\Wsdl1MetadataProvider;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Wsdl1Reader;
use VeeWee\Xml\Dom\Document;
use function Psl\Iter\first;
use function VeeWee\Xml\Dom\Configurator\loader;
use function VeeWee\Xml\Dom\Configurator\pretty_print;
use function VeeWee\Xml\Dom\Loader\xml_string_loader;

function test_schema($schema,$type,$param,$style="rpc",$use="encoded", $attributeFormDefault='',$features=0) {
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
    <schema xmlns="http://www.w3.org/2001/XMLSchema" targetNamespace="http://test-uri/" $attributeFormDefault>
     <xsd:import namespace="http://schemas.xmlsoap.org/soap/encoding/" />
     <xsd:import namespace="http://schemas.xmlsoap.org/wsdl/" />
      $schema
    </schema>
    </types>
    <message name="testMessage">
      <part name="testParam" $type/>
    </message>
      <portType name="testPortType">
          <operation name="test">
              <input message="testMessage"/>
          </operation>
      </portType>
      <binding name="testBinding" type="testPortType">
          <soap:binding style="rpc" transport="http://schemas.xmlsoap.org/soap/http"/>
          <operation name="test">
              <soap:operation soapAction="#test" style="$style"/>
              <input>
                  <soap:body use="$use" namespace="http://test-uri/" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
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

  $wsdl1 = (new Wsdl1Reader(
      new CallbackLoader(static fn (): string => $wsdl)
  ))('file.wsdl');

  $registry ??= EncoderRegistry::default();
  $metadataProvider = new Wsdl1MetadataProvider($wsdl1);
  $metadata = $metadataProvider->getMetadata();
  $driver = Driver::createFromMetadata($metadata, $wsdl1->namespaces, $registry);

  $encoded = $driver->encode('test', [$param]);
  $request = $encoded->getRequest();
  echo Document::configure(pretty_print(), loader(xml_string_loader($request)))->toXmlString();

  $method = $metadata->getMethods()->fetchByName('test');
  $param = first($method->getParameters());
  $decodeContext = new Context(
      $param->getType(),
      $metadata,
      $registry,
      $wsdl1->namespaces,
      $bindingUse = $method->getMeta()->inputBindingUsage()->map(BindingUse::from(...))->unwrapOr(BindingUse::LITERAL)
  );
  $decoder = $registry->detectEncoderForContext($decodeContext);

  $body = (new SoapEnvelopeReader())($request);
  $decoded = $decoder->iso($decodeContext)->from($body);
  var_dump($decoded);
}
?>
