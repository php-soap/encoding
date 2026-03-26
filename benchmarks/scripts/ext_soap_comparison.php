<?php
/**
 * Compares php-soap/encoding performance against PHP's native ext-soap.
 * Run with: XDEBUG_MODE=off php benchmarks/ext_soap_comparison.php [N]
 */
declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Soap\Encoding\Driver;
use Soap\Encoding\EncoderRegistry;
use Soap\Engine\HttpBinding\SoapResponse;
use Soap\Wsdl\Loader\CallbackLoader;
use Soap\WsdlReader\Metadata\Wsdl1MetadataProvider;
use Soap\WsdlReader\Wsdl1Reader;

$N = (int) ($argv[1] ?? 10000);

// ---------------------------------------------------------------------------
// Shared WSDL (written to temp file for ext-soap)
// ---------------------------------------------------------------------------

$wsdl = <<<'WSDL'
<?xml version="1.0" encoding="UTF-8"?>
<definitions name="BenchTest"
    xmlns:xsd="http://www.w3.org/2001/XMLSchema"
    xmlns:tns="http://test.benchmark/"
    xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
    xmlns="http://schemas.xmlsoap.org/wsdl/"
    targetNamespace="http://test.benchmark/">
  <types>
    <xsd:schema targetNamespace="http://test.benchmark/">
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
  <message name="testRequest"><part name="item" type="tns:itemType"/></message>
  <message name="testResponse"><part name="result" type="tns:itemType"/></message>
  <portType name="TestPortType">
    <operation name="test">
      <input message="tns:testRequest"/>
      <output message="tns:testResponse"/>
    </operation>
  </portType>
  <binding name="TestBinding" type="tns:TestPortType">
    <soap:binding style="rpc" transport="http://schemas.xmlsoap.org/soap/http"/>
    <operation name="test">
      <soap:operation soapAction="test"/>
      <input><soap:body use="literal" namespace="http://test.benchmark/"/></input>
      <output><soap:body use="literal" namespace="http://test.benchmark/"/></output>
    </operation>
  </binding>
  <service name="TestService">
    <port name="TestPort" binding="tns:TestBinding">
      <soap:address location="http://test.benchmark/soap"/>
    </port>
  </service>
</definitions>
WSDL;

$wsdlFile = tempnam(sys_get_temp_dir(), 'wsdl_') . '.wsdl';
file_put_contents($wsdlFile, $wsdl);

// ---------------------------------------------------------------------------
// Test data
// ---------------------------------------------------------------------------

// For php-soap/encoding (accepts plain objects)
$plainItem = (object) [
    'id' => 42,
    'name' => 'Widget Pro',
    'description' => 'A premium widget for demanding environments',
    'price' => 29.99,
    'quantity' => 100,
    'active' => true,
    'sku' => 'WDG-001',
    'category' => 'Electronics',
    'address' => (object) ['street' => '123 Main Street', 'city' => 'Springfield', 'zip' => '62701'],
];

// For ext-soap: nested objects need explicit type hints via SoapVar
$extSoapObj = clone $plainItem;
$extSoapObj->address = new SoapVar(
    (object) ['street' => '123 Main Street', 'city' => 'Springfield', 'zip' => '62701'],
    SOAP_ENC_OBJECT,
    'addressType',
    'http://test.benchmark/'
);
$extSoapItem = new SoapParam($extSoapObj, 'item');

// ---------------------------------------------------------------------------
// Setup: ext-soap (override __doRequest to avoid network)
// ---------------------------------------------------------------------------

class BenchSoapClient extends SoapClient
{
    public ?string $lastRequest = null;
    public ?string $mockResponse = null;

    public function __doRequest(string $request, string $location, string $action, int $version, bool $oneWay = false): ?string
    {
        $this->lastRequest = $request;
        return $this->mockResponse;
    }
}

$extSoap = new BenchSoapClient($wsdlFile, [
    'trace' => true,
    'cache_wsdl' => WSDL_CACHE_NONE,
]);

// ---------------------------------------------------------------------------
// Setup: php-soap/encoding
// ---------------------------------------------------------------------------

$wsdlObject = (new Wsdl1Reader(new CallbackLoader(static fn (): string => $wsdl)))('file.wsdl');
$registry = EncoderRegistry::default();
$metadata = (new Wsdl1MetadataProvider($wsdlObject))->getMetadata();
$driver = Driver::createFromMetadata($metadata, $wsdlObject->namespaces, $registry);

// ---------------------------------------------------------------------------
// Warmup both
// ---------------------------------------------------------------------------

// For ext-soap, __doRequest must return a valid SOAP response.
// Use a minimal empty response for encode-only benchmarks.
$minimalResponse = '<?xml version="1.0" encoding="UTF-8"?>'
    . '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">'
    . '<SOAP-ENV:Body><ns1:testResponse xmlns:ns1="http://test.benchmark/"/>'
    . '</SOAP-ENV:Body></SOAP-ENV:Envelope>';

// Full response for decode benchmarks
$fullResponse = '<?xml version="1.0" encoding="UTF-8"?>'
    . '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">'
    . '<SOAP-ENV:Body>'
    . '<ns1:testResponse xmlns:ns1="http://test.benchmark/">'
    . '<result><id>42</id><name>Widget Pro</name>'
    . '<description>A premium widget for demanding environments</description>'
    . '<price>29.99</price><quantity>100</quantity><active>true</active>'
    . '<sku>WDG-001</sku><category>Electronics</category>'
    . '<address><street>123 Main Street</street><city>Springfield</city><zip>62701</zip></address>'
    . '</result>'
    . '</ns1:testResponse>'
    . '</SOAP-ENV:Body></SOAP-ENV:Envelope>';

// ext-soap warmup
$extSoap->mockResponse = $minimalResponse;
$extSoap->__soapCall('test', ['item' => $plainItem]);
$extSoapEncoded = $extSoap->lastRequest;

// php-soap/encoding warmup
$driver->encode('test', [$plainItem]);
$phpSoapEncoded = $driver->encode('test', [$plainItem])->getRequest();

echo "=== COMPARISON: php-soap/encoding vs ext-soap ===\n";
printf("Items per run: %s\n", number_format($N));
printf("php-soap/encoding XML: %d bytes\n", strlen($phpSoapEncoded));
printf("ext-soap XML:          %d bytes\n\n", strlen($extSoapEncoded));

// ---------------------------------------------------------------------------
// Benchmark: ENCODE
// ---------------------------------------------------------------------------

echo "--- ENCODE ---\n";

// ext-soap encode (minimal response to avoid decode overhead)
$extSoap->mockResponse = $minimalResponse;
$t0 = hrtime(true);
for ($i = 0; $i < $N; $i++) {
    $extSoap->__soapCall('test', ['item' => $plainItem]);
}
$extEncUs = (hrtime(true) - $t0) / 1e3 / $N;
printf("ext-soap:          %6.1f us/item\n", $extEncUs);

// php-soap/encoding encode
$t0 = hrtime(true);
for ($i = 0; $i < $N; $i++) {
    $driver->encode('test', [$plainItem]);
}
$phpEncUs = (hrtime(true) - $t0) / 1e3 / $N;
printf("php-soap/encoding: %6.1f us/item\n", $phpEncUs);
printf("Ratio:             %.1fx slower\n\n", $phpEncUs / $extEncUs);

// ---------------------------------------------------------------------------
// Benchmark: DECODE
// ---------------------------------------------------------------------------

echo "--- DECODE ---\n";

// ext-soap decode (full response with nested objects)
$extSoap->mockResponse = $fullResponse;
$t0 = hrtime(true);
for ($i = 0; $i < $N; $i++) {
    $extSoap->__soapCall('test', ['item' => $plainItem]);  // __doRequest returns fullResponse, ext-soap parses it
}
$extDecUs = (hrtime(true) - $t0) / 1e3 / $N;
printf("ext-soap:          %6.1f us/item  (encode+decode combined)\n", $extDecUs);

// For ext-soap, we can't easily separate encode from decode since __soapCall does both.
// The decode portion is roughly: $extDecUs - $extEncUs
$extDecOnlyUs = max(0, $extDecUs - $extEncUs);
printf("ext-soap decode:   ~%.1f us/item  (estimated: combined - encode)\n", $extDecOnlyUs);

// php-soap/encoding decode
$response = new SoapResponse($fullResponse);
$t0 = hrtime(true);
for ($i = 0; $i < $N; $i++) {
    $driver->decode('test', $response);
}
$phpDecUs = (hrtime(true) - $t0) / 1e3 / $N;
printf("php-soap/encoding: %6.1f us/item\n", $phpDecUs);
if ($extDecOnlyUs > 0) {
    printf("Ratio:             %.1fx slower\n\n", $phpDecUs / $extDecOnlyUs);
} else {
    printf("Ratio:             n/a (ext-soap decode too fast to estimate)\n\n");
}

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------

echo "--- SUMMARY ---\n";
printf("Encode: php-soap/encoding is %.1fx slower than ext-soap (%.1f vs %.1f us)\n", $phpEncUs / $extEncUs, $phpEncUs, $extEncUs);
printf("Decode: php-soap/encoding is ~%.1fx slower than ext-soap (~%.1f vs ~%.1f us)\n",
    $extDecOnlyUs > 0 ? $phpDecUs / $extDecOnlyUs : 0,
    $phpDecUs,
    $extDecOnlyUs
);
printf("\nNote: ext-soap is a C extension; this comparison shows the overhead of a\n");
printf("pure PHP implementation. The gap is expected and acceptable for most use cases.\n");

// ---------------------------------------------------------------------------
// Large payload benchmark (encode-only, both implementations)
// ---------------------------------------------------------------------------

$targetMB = (int) ($argv[2] ?? 50);
$targetBytes = $targetMB * 1024 * 1024;

// Calibrate using ext-soap XML size
$extBytesPerItem = strlen($extSoapEncoded);
$phpBytesPerItem = strlen($phpSoapEncoded);
$largeN = (int) ceil($targetBytes / max($extBytesPerItem, $phpBytesPerItem));

printf("\n=== LARGE PAYLOAD (%d MB, %s items) ===\n\n", $targetMB, number_format($largeN));

// ext-soap large encode
$extSoap->mockResponse = $minimalResponse;
$extTotalBytes = 0;
$t0 = hrtime(true);
for ($i = 0; $i < $largeN; $i++) {
    $extSoap->__soapCall('test', ['item' => $plainItem]);
    $extTotalBytes += $extBytesPerItem;
}
$extLargeEncSec = (hrtime(true) - $t0) / 1e9;
$extLargeEncMB = $extTotalBytes / 1024 / 1024;
printf("ext-soap encode:\n");
printf("  Total:      %.1f MB in %.3f s\n", $extLargeEncMB, $extLargeEncSec);
printf("  Throughput: %.1f MB/s\n", $extLargeEncMB / $extLargeEncSec);
printf("  Per item:   %.1f us\n", $extLargeEncSec / $largeN * 1e6);
printf("  Peak mem:   %.1f MB\n\n", memory_get_peak_usage(true) / 1024 / 1024);

// php-soap/encoding large encode
$phpTotalBytes = 0;
$t0 = hrtime(true);
for ($i = 0; $i < $largeN; $i++) {
    $xml = $driver->encode('test', [$plainItem])->getRequest();
    $phpTotalBytes += strlen($xml);
}
$phpLargeEncSec = (hrtime(true) - $t0) / 1e9;
$phpLargeEncMB = $phpTotalBytes / 1024 / 1024;
printf("php-soap/encoding encode:\n");
printf("  Total:      %.1f MB in %.3f s\n", $phpLargeEncMB, $phpLargeEncSec);
printf("  Throughput: %.1f MB/s\n", $phpLargeEncMB / $phpLargeEncSec);
printf("  Per item:   %.1f us\n", $phpLargeEncSec / $largeN * 1e6);
printf("  Peak mem:   %.1f MB\n\n", memory_get_peak_usage(true) / 1024 / 1024);

printf("Encode ratio at scale: %.1fx slower (%.1f vs %.1f MB/s)\n",
    $phpLargeEncSec / $extLargeEncSec,
    $phpLargeEncMB / $phpLargeEncSec,
    $extLargeEncMB / $extLargeEncSec
);

// ext-soap large decode
$extSoap->mockResponse = $fullResponse;
$t0 = hrtime(true);
for ($i = 0; $i < $largeN; $i++) {
    $extSoap->__soapCall('test', ['item' => $plainItem]);
}
$extLargeDecSec = (hrtime(true) - $t0) / 1e9;
// Subtract encode time to estimate decode-only
$extLargeDecOnlySec = max(0, $extLargeDecSec - $extLargeEncSec);
$extLargeDecMB = $largeN * strlen($fullResponse) / 1024 / 1024;

printf("\next-soap decode (estimated):\n");
printf("  Throughput: ~%.1f MB/s\n", $extLargeDecOnlySec > 0 ? $extLargeDecMB / $extLargeDecOnlySec : 0);
printf("  Per item:   ~%.1f us\n", $extLargeDecOnlySec / $largeN * 1e6);

// php-soap/encoding large decode
$response = new SoapResponse($fullResponse);
$t0 = hrtime(true);
for ($i = 0; $i < $largeN; $i++) {
    $driver->decode('test', $response);
}
$phpLargeDecSec = (hrtime(true) - $t0) / 1e9;
$phpLargeDecMB = $largeN * strlen($fullResponse) / 1024 / 1024;

printf("\nphp-soap/encoding decode:\n");
printf("  Throughput: %.1f MB/s\n", $phpLargeDecMB / $phpLargeDecSec);
printf("  Per item:   %.1f us\n", $phpLargeDecSec / $largeN * 1e6);

if ($extLargeDecOnlySec > 0) {
    printf("\nDecode ratio at scale: ~%.1fx slower (%.1f vs ~%.1f MB/s)\n",
        $phpLargeDecSec / $extLargeDecOnlySec,
        $phpLargeDecMB / $phpLargeDecSec,
        $extLargeDecMB / $extLargeDecOnlySec
    );
}

unlink($wsdlFile);
