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
final class ImpliedSchema007Test extends AbstractCompatibilityTests
{
    protected string $schema = <<<EOXML
    <element name="testType">
        <complexType>
            <sequence>
                <any processContents="strict" minOccurs="0" maxOccurs="1" />
            </sequence>
        </complexType>
    </element>
    EOXML;
    protected string $type = 'type="tns:testType"';

    protected function calculateParam(): mixed
    {
        return (object)[
            'any' => null
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
                    <testParam xsi:type="tns:testType" />
                </tns:test>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
        XML;
    }
}
