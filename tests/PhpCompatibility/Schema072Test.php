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
final class Schema072Test extends AbstractCompatibilityTests
{
    protected string $schema = <<<EOXML
    <element name="testElement">
    <complexType name="testType">
        <complexContent>
            <restriction base="SOAP-ENC:Array">
        <attribute ref="SOAP-ENC:arrayType" wsdl:arrayType="int[]"/>
        </restriction>
    </complexContent>
    </complexType>
    </element>
    EOXML;
    protected string $type = 'type="tns:testElement"';
    protected string $style = 'document';
    protected string $use = 'literal';

    protected function calculateParam(): mixed
    {
        return [
            123,
            123,
        ];
    }

    protected function expectXml(): string
    {
        return <<<XML
        <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"
                           xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:tns="http://test-uri/">
            <SOAP-ENV:Body>
                <tns:testElement>
                    <xsd:int>123</xsd:int>
                    <xsd:int>123</xsd:int>
                </tns:testElement>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
        XML;
    }
}
