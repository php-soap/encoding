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
final class ImpliedSchema006Test extends AbstractCompatibilityTests
{
    protected string $schema = <<<EOXML
    <element name="testType">
        <complexType>
            <sequence>
                <any processContents="strict" minOccurs="0" maxOccurs="3" />
            </sequence>
        </complexType>
    </element>
    EOXML;
    protected string $type = 'type="tns:testType"';

    protected function calculateParam(): mixed
    {
        return (object)[
            'any' => [
                '<customerName>John Doe</customerName>',
                '<customerEmail>john@doe.com</customerEmail>',
                '<hello>world</hello>',
                '<hello>moon</hello>',
            ],
        ];
    }

    protected function expectXml(): string
    {
        return <<<XML
         <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns="http://test-uri/"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/">
            <SOAP-ENV:Body SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                <tns:test>
                    <testParam xsi:type="tns:testType">
                        <customerName>John Doe</customerName>
                        <customerEmail>john@doe.com</customerEmail>
                        <hello>world</hello>
                        <hello>moon</hello>
                    </testParam>
                </tns:test>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
        XML;
    }
}
