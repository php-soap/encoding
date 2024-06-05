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
final class Schema064Test extends AbstractCompatibilityTests
{
    protected string $schema = <<<EOXML
    <complexType name="testType">
        <sequence>
            <element name="dateTime" type="dateTime"/>
            <element name="time" type="time"/>
            <element name="date" type="date"/>
            <element name="gYearMonth" type="gYearMonth"/>
            <element name="gYear" type="gYear"/>
            <element name="gMonthDay" type="gMonthDay"/>
            <element name="gDay" type="gDay"/>
            <element name="gMonth" type="gMonth"/>
        </sequence>
    </complexType>
    EOXML;
    protected string $type = 'type="tns:testType"';

    protected function calculateParam(): mixed
    {
        $date = gmmktime(1, 2, 3, 4, 5, 1976);
        putenv('TZ=GMT');

        return (object)[
            'dateTime' => $date,
            'time' => $date,
            'date' => $date,
            'gYearMonth' => $date,
            'gYear' => $date,
            'gMonthDay' => $date,
            'gDay' => $date,
            'gMonth' => $date,
        ];
    }

    #[Test]
    public function it_is_compatible_with_phps_encoding()
    {
        static::markTestSkipped('Epoch numbers are not supported currently - we only support dateTimeInterface. See Schema086.');
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
                        <dateTime xsi:type="xsd:dateTime">1976-04-05T01:02:03+00:00</dateTime>
                        <time xsi:type="xsd:time">01:02:03Z</time>
                        <date xsi:type="xsd:date">1976-04-05</date>
                        <gYearMonth xsi:type="xsd:gYearMonth">1976-04Z</gYearMonth>
                        <gYear xsi:type="xsd:gYear">1976Z</gYear>
                        <gMonthDay xsi:type="xsd:gMonthDay">--04-05Z</gMonthDay>
                        <gDay xsi:type="xsd:gDay">---05Z</gDay>
                        <gMonth xsi:type="xsd:gMonth">--04--Z</gMonth>
                    </testParam>
                </tns:test>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
        XML;
    }
}
