<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\PhpCompatibility\Implied;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Decoder;
use Soap\Encoding\Driver;
use Soap\Encoding\Encoder;
use Soap\Encoding\Test\PhpCompatibility\AbstractCompatibilityTests;

#[CoversClass(Driver::class)]
#[CoversClass(Encoder::class)]
#[CoversClass(Decoder::class)]
#[CoversClass(Encoder\SoapEnc\SoapArrayEncoder::class)]
final class ImpliedSchema013Test extends AbstractCompatibilityTests
{
    protected string $schema = <<<EOXML
    <complexType name="Foo">
        <all>
            <element name="id" type="string" />
        </all>
    </complexType>
    <complexType name="ArrayOfFoo" xmlns:soap-enc="http://schemas.xmlsoap.org/soap/encoding/">
        <complexContent>
            <restriction base="soap-enc:Array">
                <attribute ref="soap-enc:arrayType" wsdl:arrayType="tns:Foo[]"/>
            </restriction>
        </complexContent>
    </complexType>
    <element name="testType" minOccurs="1" maxOccurs="1" type="tns:ArrayOfFoo" />
    EOXML;
    protected string $type = 'type="tns:ArrayOfFoo"';

    protected function calculateParam(): mixed
    {
        return [
            (object)['id' => 'abc'],
            (object)['id' => 'def'],
        ];
    }

    protected function expectXml(): string
    {
        return <<<XML
         <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"
            xmlns:tns="http://test-uri/"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xmlns:xsd="http://www.w3.org/2001/XMLSchema"
            xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/">
            <SOAP-ENV:Body SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                <tns:test>
                    <testParam SOAP-ENC:arrayType="tns:Foo[2]" xsi:type="tns:ArrayOfFoo">
                        <item xsi:type="tns:Foo">
                          <id xsi:type="xsd:string">abc</id>
                        </item>
                        <item xsi:type="tns:Foo">
                          <id xsi:type="xsd:string">def</id>
                        </item>
                    </testParam>
                </tns:test>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
        XML;
    }
}
