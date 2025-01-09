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
final class ImpliedSchema009Test extends AbstractCompatibilityTests
{
    protected string $schema = <<<EOXML
    <element name="testType" minOccurs="0" maxOccurs="1" type="tns:ArrayOfCompany" />
    <complexType name="ArrayOfCompany">
        <sequence>
            <element minOccurs="0" maxOccurs="unbounded" name="Company" nillable="true" type="tns:Company" />
        </sequence>
    </complexType>
    <complexType name="Company">
        <sequence>
            <element minOccurs="1" maxOccurs="1" name="ID" type="xsd:int" />
        </sequence>
    </complexType>
    EOXML;
    protected string $type = 'type="tns:testType"';

    protected function calculateParam(): mixed
    {
        return (object)[
            'Company' => [
                (object)['ID' => 0],
                (object)['ID' => 1],
            ],
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
                    <testParam xsi:type="tns:ArrayOfCompany">
                        <Company xsi:type="tns:Company">
                            <ID xsi:type="xsd:int">0</ID>
                        </Company>
                        <Company xsi:type="tns:Company">
                            <ID xsi:type="xsd:int">1</ID>
                        </Company>
                    </testParam>
                </tns:test>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
        XML;
    }
}
