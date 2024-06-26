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
#[CoversClass(Encoder\SimpleType\AttributeValueEncoder::class)]
final class Schema065Test extends AbstractCompatibilityTests
{
    protected string $schema = <<<EOXML
    <complexType name="testType">
        <attribute name="str" type="string"/>
        <attribute name="int" type="int" default="5"/>
    </complexType>
    EOXML;
    protected string $type = 'type="tns:testType"';

    protected function calculateParam(): mixed
    {
        return (object)[
            'str' => 'str',
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
                        <testParam str="str" xsi:type="tns:testType"/>
                    </tns:test>
                </SOAP-ENV:Body>
            </SOAP-ENV:Envelope>
        XML;
    }

    protected function expectDecoded(): mixed
    {
        return (object)[
            'str' => 'str',
            'int' => 5,
        ];
    }
}
