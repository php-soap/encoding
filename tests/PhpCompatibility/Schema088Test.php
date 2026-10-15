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
final class Schema088Test extends AbstractCompatibilityTests
{
    protected string $schema = <<<EOXML
    </schema>
    <schema xmlns="http://www.w3.org/2001/XMLSchema" targetNamespace="urn:other">
        <attribute name="flag" type="string"/>
    </schema>
    <schema xmlns="http://www.w3.org/2001/XMLSchema" xmlns:o="urn:other" targetNamespace="http://test-uri/">
    <import namespace="urn:other"/>
    <complexType name="testType">
        <sequence>
            <element name="str" type="string"/>
        </sequence>
        <attribute ref="o:flag"/>
    </complexType>
    EOXML;
    protected string $type = 'type="tns:testType"';

    protected function calculateParam(): mixed
    {
        return (object)[
            "str" => "str",
            "flag" => "on",
        ];
    }

    /**
     * ext-soap writes flag="on" without namespace. A global attribute is always qualified in XSD,
     * so the reference to o:flag is written in the namespace of the schema that declares it.
     */
    protected function expectXml(): string
    {
        return <<<XML
        <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns="http://test-uri/"
                           xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                           xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" xmlns:o="urn:other">
            <SOAP-ENV:Body SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                <tns:test>
                    <testParam o:flag="on" xsi:type="tns:testType">
                        <str xsi:type="xsd:string">str</str>
                    </testParam>
                </tns:test>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
        XML;
    }
}
