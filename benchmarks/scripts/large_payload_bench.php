<?php

/**
 * Large payload benchmark: encodes/decodes ~500MB of XML data and reports throughput.
 * Run with: XDEBUG_MODE=off php benchmarks/large_payload_bench.php
 */

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Soap\Encoding\Driver;
use Soap\Encoding\EncoderRegistry;
use Soap\Engine\HttpBinding\SoapResponse;
use Soap\Wsdl\Loader\CallbackLoader;
use Soap\WsdlReader\Metadata\Wsdl1MetadataProvider;
use Soap\WsdlReader\Wsdl1Reader;

// ---------------------------------------------------------------------------
// WSDL setup (outside timing loop)
// ---------------------------------------------------------------------------

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
                <soap:body use="literal" namespace="http://test-uri/"/>
            </input>
            <output>
                <soap:body use="literal" namespace="http://test-uri/"/>
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

$wsdlObject = (new Wsdl1Reader(new CallbackLoader(static fn (): string => $wsdl)))('file.wsdl');
$registry = EncoderRegistry::default();
$metadata = (new Wsdl1MetadataProvider($wsdlObject))->getMetadata();
$driver = Driver::createFromMetadata($metadata, $wsdlObject->namespaces, $registry);

// ---------------------------------------------------------------------------
// Generate realistic item data
// ---------------------------------------------------------------------------

$streets = [
    '742 Evergreen Terrace', '221B Baker Street', '1600 Pennsylvania Avenue',
    '350 Fifth Avenue', '10 Downing Street', '1 Infinite Loop',
    '4059 Mount Lee Drive', '1060 West Addison Street',
];
$cities = [
    'Springfield', 'London', 'Washington', 'New York', 'Manchester',
    'Cupertino', 'Los Angeles', 'Chicago', 'Portland', 'Seattle',
];
$categories = [
    'Electronics', 'Home & Garden', 'Sporting Goods', 'Automotive Parts',
    'Books & Media', 'Health & Beauty', 'Office Supplies', 'Industrial Equipment',
    'Clothing & Apparel', 'Food & Beverages',
];
$adjectives = [
    'Premium', 'Professional', 'Heavy-Duty', 'Lightweight', 'Industrial',
    'Compact', 'Deluxe', 'Standard', 'Advanced', 'Ultra',
];
$nouns = [
    'Widget', 'Gadget', 'Component', 'Module', 'Assembly',
    'Adapter', 'Connector', 'Bracket', 'Sensor', 'Controller',
];
$descriptions = [
    'Designed for demanding environments and continuous operation under heavy loads',
    'Precision-engineered component manufactured to exacting tolerances',
    'Versatile solution suitable for both commercial and residential applications',
    'High-performance unit featuring advanced thermal management system',
    'Ergonomic design with reinforced housing for extended service life',
    'Cost-effective replacement part compatible with all major brands',
    'Next-generation technology delivering improved efficiency and reliability',
    'Rugged construction with IP67 rating for outdoor installations',
];

function buildItem(int $i): object
{
    global $streets, $cities, $categories, $adjectives, $nouns, $descriptions;

    return (object) [
        'id' => $i,
        'name' => $adjectives[$i % count($adjectives)] . ' ' . $nouns[$i % count($nouns)] . ' MK-' . $i,
        'description' => $descriptions[$i % count($descriptions)] . ' (ref #' . $i . ')',
        'price' => round(4.99 + ($i % 500) * 0.73, 2),
        'quantity' => 1 + ($i % 9999),
        'active' => $i % 3 !== 0,
        'sku' => 'SKU-' . strtoupper(dechex($i + 0xA000)) . '-' . str_pad((string) ($i % 10000), 4, '0', STR_PAD_LEFT),
        'category' => $categories[$i % count($categories)],
        'address' => (object) [
            'street' => ($i * 7 % 9999) . ' ' . $streets[$i % count($streets)],
            'city' => $cities[$i % count($cities)],
            'zip' => str_pad((string) (10000 + $i % 89999), 5, '0', STR_PAD_LEFT),
        ],
    ];
}

// ---------------------------------------------------------------------------
// Calibrate: encode one item to measure XML size, then derive N for ~500MB
// ---------------------------------------------------------------------------

$sampleItem = buildItem(0);

// Warmup
$driver->encode('test', [$sampleItem]);

$sampleXml = $driver->encode('test', [$sampleItem])->getRequest();
$bytesPerItem = strlen($sampleXml);

$targetMB = (int) ($argv[1] ?? 50);
$targetBytes = $targetMB * 1024 * 1024;
$N = (int) ceil($targetBytes / $bytesPerItem);

printf("XML size per item:  %d bytes\n", $bytesPerItem);
printf("Items needed:       %s (targeting %d MB)\n", number_format($N), $targetBytes / 1024 / 1024);
printf("Expected total XML: %.1f MB\n\n", ($N * $bytesPerItem) / 1024 / 1024);

// ---------------------------------------------------------------------------
// Benchmark: Encode (generate items on the fly to avoid memory exhaustion)
// ---------------------------------------------------------------------------

echo "=== ENCODE ===\n";
$totalEncodeBytes = 0;
$encodedXmls = [];

$t0 = hrtime(true);
for ($i = 0; $i < $N; $i++) {
    $xml = $driver->encode('test', [buildItem($i)])->getRequest();
    $totalEncodeBytes += strlen($xml);
    if ($i < 1000) {
        $encodedXmls[] = $xml;
    }
}
$encodeNs = hrtime(true) - $t0;

$encodeMs = $encodeNs / 1e6;
$encodeSec = $encodeNs / 1e9;
$encodeMB = $totalEncodeBytes / 1024 / 1024;

printf("Total XML produced: %.1f MB\n", $encodeMB);
printf("Encode time:        %.3f s\n", $encodeSec);
printf("Throughput:         %.1f MB/s\n", $encodeMB / $encodeSec);
printf("Per item:           %.1f us\n", $encodeNs / 1e3 / $N);
printf("Peak memory:        %.1f MB\n\n", memory_get_peak_usage(true) / 1024 / 1024);

// ---------------------------------------------------------------------------
// Benchmark: Decode
// ---------------------------------------------------------------------------

// For decode, we cycle through the stored subset of encoded XMLs.
// This measures decode throughput over the same ~500MB volume without
// needing to keep all encoded strings in memory.

echo "=== DECODE ===\n";
$totalDecodeBytes = 0;
$subsetCount = count($encodedXmls);

$t0 = hrtime(true);
for ($i = 0; $i < $N; $i++) {
    $xml = $encodedXmls[$i % $subsetCount];
    $response = new SoapResponse($xml);
    $driver->decode('test', $response);
    $totalDecodeBytes += strlen($xml);
}
$decodeNs = hrtime(true) - $t0;

$decodeSec = $decodeNs / 1e9;
$decodeMB = $totalDecodeBytes / 1024 / 1024;

printf("Total XML decoded:  %.1f MB\n", $decodeMB);
printf("Decode time:        %.3f s\n", $decodeSec);
printf("Throughput:         %.1f MB/s\n", $decodeMB / $decodeSec);
printf("Per item:           %.1f us\n", $decodeNs / 1e3 / $N);
printf("Peak memory:        %.1f MB\n\n", memory_get_peak_usage(true) / 1024 / 1024);

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------

echo "=== SUMMARY ===\n";
printf("Items:              %s\n", number_format($N));
printf("Encode throughput:  %.1f MB/s  (%.3f s)\n", $encodeMB / $encodeSec, $encodeSec);
printf("Decode throughput:  %.1f MB/s  (%.3f s)\n", $decodeMB / $decodeSec, $decodeSec);
printf("Peak memory:        %.1f MB\n", memory_get_peak_usage(true) / 1024 / 1024);
