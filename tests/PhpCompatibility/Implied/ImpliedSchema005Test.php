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
#[CoversClass(Encoder\AnyElementEncoder::class)]
final class ImpliedSchema005Test extends AbstractCompatibilityTests
{
    protected string $schema = <<<EOXML
    <element name="testType">
        <complexType>
            <sequence>
                <element name="customerName" type="xsd:string" />
                <element name="customerEmail" type="xsd:string" />
                <any processContents="strict" minOccurs="0" maxOccurs="3" />
            </sequence>
        </complexType>
    </element>
    EOXML;
    protected string $type = 'type="tns:testType"';

    protected function calculateParam(): mixed
    {
        return (object)[
            'customerName' => 'John Doe',
            'customerEmail' => 'john@doe.com',
            'any' => [
                '<hello>world</hello>',
                '<hello>moon</hello>',
            ],
        ];
    }

    protected function expectXml(): string
    {
        return <<<XML
         <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns="http://test-uri/"
            xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/">
            <SOAP-ENV:Body SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                <tns:test>
                    <testParam xsi:type="tns:testType">
                        <customerName xsi:type="xsd:string">John Doe</customerName>
                        <customerEmail xsi:type="xsd:string">john@doe.com</customerEmail>
                        <hello>world</hello>
                        <hello>moon</hello>
                    </testParam>
                </tns:test>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
        XML;
    }
}
