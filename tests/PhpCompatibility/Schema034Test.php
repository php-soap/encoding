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
final class Schema034Test extends AbstractCompatibilityTests
{
    protected string $schema = <<<EOXML
    <element name="testType2" type="tns:testType2"/>
    <complexType name="testType2">
        <sequence>
            <element name="int" type="int"/>
        </sequence>
    </complexType>
    <complexType name="testType">
        <sequence>
            <element name="int" type="int"/>
            <element ref="tns:testType2"/>
        </sequence>
    </complexType>
    EOXML;
    protected string $type = 'type="tns:testType"';

    protected function calculateParam(): mixed
    {
        return (object)[
            "int" => 123,
            'testType2' => (object)[
                'int' => 123,
            ]
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
                        <int xsi:type="xsd:int">123</int>
                        <testType2 xsi:type="tns:testType2">
                            <int xsi:type="xsd:int">123</int>
                        </testType2>
                    </testParam>
                </tns:test>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
        XML;
    }
}
