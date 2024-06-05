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
final class Schema018Test extends AbstractCompatibilityTests
{
    protected string $schema = <<<EOXML
    <simpleType name="testType">
        <union>
            <simpleType>
                <restriction base="float"/>
            </simpleType>
            <simpleType>
                <list itemType="int"/>
            </simpleType>
        </union>
    </simpleType>
    EOXML;
    protected string $type = 'type="tns:testType"';
    protected mixed $param = '123.5 456.7';

    protected function expectXml(): string
    {
        return <<<XML
        <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns="http://test-uri/"
                           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                           xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/">
            <SOAP-ENV:Body SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                <tns:test>
                    <testParam xsi:type="tns:testType">123.5 456.7</testParam>
                </tns:test>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
        XML;
    }

    protected function expectDecoded(): mixed
    {
        return ['123.5', '456.7'];
    }
}
