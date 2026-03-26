<?php
/**
 * Internal profiling: breaks down encode and decode into their sub-operations.
 * Run with: php -d xdebug.mode=off profile_encode.php
 */
require __DIR__ . '/../../vendor/autoload.php';

use Soap\Encoding\Driver;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\EncoderRegistry;
use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Reader\OperationReader;
use Soap\Engine\HttpBinding\SoapResponse;
use Soap\Wsdl\Loader\CallbackLoader;
use Soap\WsdlReader\Metadata\Wsdl1MetadataProvider;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Wsdl1Reader;
use function Psl\Iter\first;
use function Psl\Vec\map;

$schema = <<<'EOXML'
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
EOXML;

$wsdl = <<<EOF
<definitions name="BenchTest"
    xmlns:xsd="http://www.w3.org/2001/XMLSchema"
    xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
    xmlns:tns="http://test-uri/"
    xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
    xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"
    xmlns="http://schemas.xmlsoap.org/wsdl/"
    targetNamespace="http://test-uri/"
    >
  <types>
  <schema xmlns="http://www.w3.org/2001/XMLSchema" targetNamespace="http://test-uri/">
   <xsd:import namespace="http://schemas.xmlsoap.org/soap/encoding/" />
   <xsd:import namespace="http://schemas.xmlsoap.org/wsdl/" />
    {$schema}
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
            <input>
                <soap:body use="encoded" namespace="http://test-uri/" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
            </input>
            <output>
                <soap:body use="encoded" namespace="http://test-uri/" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
            </output>
        </operation>
    </binding>
    <service name="testService">
   <port name="testPort" binding="tns:testBinding">
     <soap:address location="test://" />
   </port>
 </service>
</definitions>
EOF;

// Setup
$wsdlObject = (new Wsdl1Reader(new CallbackLoader(static fn (): string => $wsdl)))('file.wsdl');
$registry = EncoderRegistry::default();
$metadata = (new Wsdl1MetadataProvider($wsdlObject))->getMetadata();
$driver = Driver::createFromMetadata($metadata, $wsdlObject->namespaces, $registry);

$item = (object) [
    'id' => 42, 'name' => 'Widget', 'description' => 'A widget',
    'price' => 29.99, 'quantity' => 100, 'active' => true,
    'sku' => 'WDG-001', 'category' => 'Electronics',
    'address' => (object) ['street' => '123 Main', 'city' => 'Springfield', 'zip' => '62701'],
];

$N = (int) ($argv[1] ?? 1000);

// Warmup
$driver->encode('test', [$item]);
$xml = $driver->encode('test', [$item])->getRequest();
$response = new SoapResponse($xml);
$driver->decode('test', $response);

echo "=== END-TO-END ($N iterations) ===\n";
$t0 = hrtime(true);
for ($i = 0; $i < $N; $i++) $driver->encode('test', [$item]);
printf("Encode: %.1fms total, %.1fus/item\n", ($t = (hrtime(true) - $t0) / 1e6), $t / $N * 1000);

$t0 = hrtime(true);
for ($i = 0; $i < $N; $i++) $driver->decode('test', $response);
printf("Decode: %.1fms total, %.1fus/item\n\n", ($t = (hrtime(true) - $t0) / 1e6), $t / $N * 1000);

// ---- Break down ENCODE ----
echo "=== ENCODE BREAKDOWN ($N iterations) ===\n";

// Step 1: Encoder setup (method lookup, iso creation)
$method = $metadata->getMethods()->fetchByName('test');
$methodContext = new \Soap\Encoding\Encoder\Method\MethodContext($method, $metadata, $registry, $wsdlObject->namespaces);
$requestEncoder = new \Soap\Encoding\Encoder\Method\RequestEncoder();
// Pre-build iso once to separate setup from execution
$encIso = $requestEncoder->iso($methodContext);

$t0 = hrtime(true);
for ($i = 0; $i < $N; $i++) {
    $requestEncoder->iso($methodContext);
}
printf("RequestEncoder->iso():                %6.1fus/call\n", (hrtime(true) - $t0) / 1e3 / $N);

// Step 2: iso->to (the actual encode)
$t0 = hrtime(true);
for ($i = 0; $i < $N; $i++) {
    $encIso->to([$item]);
}
printf("iso->to([\$item]):                     %6.1fus/call\n", (hrtime(true) - $t0) / 1e3 / $N);

// Step 3: Just the inner encoder (type encoder, not envelope)
$paramType = first($method->getParameters())->getType();
$encContext = $methodContext->createXmlEncoderContextForType($paramType);
$typeEncoder = $registry->detectEncoderForContext($encContext);
$typeIso = $typeEncoder->iso($encContext);

$t0 = hrtime(true);
for ($i = 0; $i < $N; $i++) {
    $typeIso->to($item);
}
printf("typeIso->to(\$item) (no envelope):     %6.1fus/call\n", (hrtime(true) - $t0) / 1e3 / $N);

// Step 4: Just XsdTypeXmlElementWriter
$t0 = hrtime(true);
for ($i = 0; $i < $N; $i++) {
    (new \Soap\Encoding\Xml\Writer\XsdTypeXmlElementWriter())(
        $encContext,
        \VeeWee\Xml\Writer\Builder\value('test')
    );
}
printf("XsdTypeXmlElementWriter (simple):     %6.1fus/call\n\n", (hrtime(true) - $t0) / 1e3 / $N);

// ---- Break down DECODE ----
echo "=== DECODE BREAKDOWN ($N iterations) ===\n";

// Step 1: SOAP envelope parse
$t0 = hrtime(true);
for ($i = 0; $i < $N; $i++) {
    (new \Soap\Encoding\Xml\Reader\SoapEnvelopeReader())($xml);
}
printf("SoapEnvelopeReader (parse+fault):     %6.1fus/call\n", (hrtime(true) - $t0) / 1e3 / $N);

// Step 2: OperationReader (envelope + extract parts)
$methodMeta = $method->getMeta();
$t0 = hrtime(true);
for ($i = 0; $i < $N; $i++) {
    (new OperationReader($methodMeta))($xml);
}
printf("OperationReader (full):               %6.1fus/call\n", (hrtime(true) - $t0) / 1e3 / $N);

// Step 3: Just the type decoder (from Element, no envelope parse)
$parts = (new OperationReader($methodMeta))($xml);
$partElement = first($parts->elements());

$returnType = $method->getReturnType();
$decContext = $methodContext->createXmlEncoderContextForType($returnType);
$decContext = new Context(
    $returnType,
    $metadata,
    $registry,
    $wsdlObject->namespaces,
    $methodMeta->outputBindingUsage()
        ->map(BindingUse::from(...))
        ->unwrapOr(BindingUse::LITERAL)
);
$typeDecoder = $registry->detectEncoderForContext($decContext);
$decIso = $typeDecoder->iso($decContext);

$t0 = hrtime(true);
for ($i = 0; $i < $N; $i++) {
    $decIso->from($partElement);
}
printf("typeIso->from(\$element) (no parse):   %6.1fus/call\n", (hrtime(true) - $t0) / 1e3 / $N);

// Step 4: Just DocumentToLookupArrayReader
$t0 = hrtime(true);
for ($i = 0; $i < $N; $i++) {
    (new \Soap\Encoding\Xml\Reader\DocumentToLookupArrayReader())($partElement);
}
printf("DocumentToLookupArrayReader:          %6.1fus/call\n", (hrtime(true) - $t0) / 1e3 / $N);

// Step 5: object_data from (properties_set)
$objectDataIso = \VeeWee\Reflecta\Iso\object_data(\stdClass::class);
$propData = ['id' => 42, 'name' => 'Widget', 'description' => 'A widget', 'price' => 29.99, 'quantity' => 100, 'active' => true, 'sku' => 'WDG-001', 'category' => 'Electronics', 'address' => null];
$t0 = hrtime(true);
for ($i = 0; $i < $N; $i++) {
    $objectDataIso->from($propData);
}
printf("object_data->from (properties_set):   %6.1fus/call\n", (hrtime(true) - $t0) / 1e3 / $N);

echo "\nPeak memory: " . round(memory_get_peak_usage(true) / 1024 / 1024, 1) . "MB\n";
