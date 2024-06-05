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
final class Schema079Test extends AbstractCompatibilityTests
{
    protected string $schema = <<<EOXML
    <complexType name="testType">
        <sequence>
            <element name="int1" type="int"/>
            <element name="int2" type="int" form="qualified"/>
            <element name="int3" type="int" form="unqualified"/>
        </sequence>
    </complexType>
    EOXML;
    protected string $type = 'type="tns:testType"';
    protected string $use = 'literal';
    protected string $attributeFormDefault = 'elementFormDefault="unqualified"';

    protected function calculateParam(): mixed
    {
        return (object)[
            'int1' => 1,
            'int2' => 2,
            'int3' => 3,
        ];
    }

    protected function expectXml(): string
    {
        return <<<XML
        <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns="http://test-uri/">
            <SOAP-ENV:Body>
                <tns:test>
                    <testParam>
                        <int1>1</int1>
                        <tns:int2>2</tns:int2>
                        <int3>3</int3>
                    </testParam>
                </tns:test>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
        XML;
    }
}
