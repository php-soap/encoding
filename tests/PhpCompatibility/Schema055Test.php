<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\PhpCompatibility;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Decoder;
use Soap\Encoding\Driver;
use Soap\Encoding\Encoder;

#[CoversClass(Driver::class)]
#[CoversClass(Encoder::class)]
#[CoversClass(Decoder::class)]
#[CoversClass(Encoder\SoapEnc\ApacheMapEncoder::class)]
final class Schema055Test extends AbstractCompatibilityTests
{
    protected string $schema = <<<EOXML
    <complexType name="testType">
        <complexContent>
            <extension base="apache:Map" xmlns:apache="http://xml.apache.org/xml-soap">
            </extension>
        </complexContent>
    </complexType>
    EOXML;
    protected string $type = 'type="testType"';

    protected function calculateParam(): mixed
    {
        return [
            "a" => 123,
            "b" => 123.5,
        ];
    }

    protected function expectXml(): string
    {
        return <<<XML
        <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns="http://test-uri/"
                           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                           xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/">
            <SOAP-ENV:Body SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                <tns:test>
                    <testParam xsi:type="tns:testType">
                        <item>
                            <key xsi:type="xsd:string">a</key>
                            <value xsi:type="xsd:int">123</value>
                        </item>
                        <item>
                            <key xsi:type="xsd:string">b</key>
                            <value xsi:type="xsd:float">123.5</value>
                        </item>
                    </testParam>
                </tns:test>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
        XML;
    }
}
