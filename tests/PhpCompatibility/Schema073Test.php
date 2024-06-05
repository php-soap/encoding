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
class Schema073Test extends AbstractCompatibilityTests
{
    protected string $schema = <<<EOXML
    <element name="testElement" type="tns:testType"/>
    <complexType name="testType">
        <complexContent>
            <restriction base="SOAP-ENC:Array">
        <attribute ref="SOAP-ENC:arrayType" wsdl:arrayType="int[]"/>
        </restriction>
    </complexContent>
    </complexType>
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

    #[Test]
    public function it_is_compatible_with_phps_encoding()
    {
        $this->markTestSkipped('Literal document seems about right - yet php soap uses the type instead of the part name. Not sure what to do here yet.');
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
