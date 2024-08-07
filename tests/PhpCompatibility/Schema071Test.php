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
final class Schema071Test extends AbstractCompatibilityTests
{
    protected string $schema = <<<EOXML
    <complexType name="testType">
        <complexContent>
            <restriction base="SOAP-ENC:Array">
        <attribute ref="SOAP-ENC:arrayType" wsdl:arrayType="int[]"/>
        </restriction>
    </complexContent>
    </complexType>
    EOXML;
    protected string $type = 'type="tns:testType"';
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
        static::markTestSkipped('Literal document seems about right - it seems to be taking the param name instead of the complextype name though. Not sure what to do here yet.');
    }

    protected function expectXml(): string
    {
        return <<<XML
        <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"
                           xmlns:xsd="http://www.w3.org/2001/XMLSchema">
            <SOAP-ENV:Body>
                <testParam>
                    <xsd:int>123</xsd:int>
                    <xsd:int>123</xsd:int>
                </testParam>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
        XML;
    }
}
