<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\PhpCompatibility;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Soap\Encoding\Decoder;
use Soap\Encoding\Driver;
use Soap\Encoding\Encoder;

#[CoversClass(Driver::class)]
#[CoversClass(Encoder::class)]
#[CoversClass(Decoder::class)]
class Schema083Test extends AbstractCompatibilityTests
{
    #[Test] public function it_is_compatible_with_phps_encoding()
    {
        $this->markTestSkipped('We currently dont support the SOAP_USE_XSI_ARRAY_TYPE way in here');
    }

    protected function expectXml(): string
    {
        return <<<'EOORIGNALTEST'
--TEST--
SOAP XML Schema 83: SOAP 1.2 Array with SOAP_USE_XSI_ARRAY_TYPE

--FILE--
<?php
include __DIR__."/test_schema.inc";
$schema = <<<EOF
    <complexType name="testType">
        <complexContent>
            <restriction base="enc12:Array" xmlns:enc12="http://www.w3.org/2003/05/soap-encoding">
        <attribute ref="enc12:itemType" wsdl:itemType="int"/>
        <attribute ref="enc12:arraySize" wsdl:arraySize="*"/>
        </restriction>
    </complexContent>
    </complexType>
EOF;
test_schema($schema,'type="tns:testType"',array(123,123.5),"rpc","encoded",'',SOAP_USE_XSI_ARRAY_TYPE);
echo "ok";
?>
--EXPECT--
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://test-uri/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><SOAP-ENV:Body><ns1:test><testParam SOAP-ENC:arrayType="xsd:int[2]" xsi:type="SOAP-ENC:Array"><item xsi:type="xsd:int">123</item><item xsi:type="xsd:int">123</item></testParam></ns1:test></SOAP-ENV:Body></SOAP-ENV:Envelope>
array(2) {
  [0]=>
  int(123)
  [1]=>
  int(123)
}
ok

EOORIGNALTEST;
    }
}
