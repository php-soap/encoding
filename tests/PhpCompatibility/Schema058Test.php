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
final class Schema058Test extends AbstractCompatibilityTests
{
    protected string $schema = <<<EOXML
    <complexType name="testType">
        <complexContent>
            <restriction base="enc12:Array" xmlns:enc12="http://www.w3.org/2003/05/soap-encoding">
        <attribute ref="enc12:itemType" wsdl:itemType="int"/>
        <attribute ref="enc12:arraySize" wsdl:arraySize="*"/>
        </restriction>
    </complexContent>
    </complexType>
    EOXML;
    protected string $type = 'type="testType"';
    protected string $style = 'rpc';
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
        <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns="http://test-uri/"
                           xmlns:xsd="http://www.w3.org/2001/XMLSchema">
            <SOAP-ENV:Body>
                <tns:test>
                    <testParam>
                        <xsd:int>123</xsd:int>
                        <xsd:int>123</xsd:int>
                    </testParam>
                </tns:test>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
        XML;
    }
}
