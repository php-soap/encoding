<?php

declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Xml\Writer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Xml\Writer\SoapEnvelopeWriter;
use Soap\WsdlReader\Model\Definitions\SoapVersion;

#[CoversClass(SoapEnvelopeWriter::class)]
class SoapEnvelopeWriterTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideEnvelopeCases
     */
    public function it_can_write_a_soap_envelope(SoapVersion $version, string $xml, string $expected): void
    {
        $writer = new SoapEnvelopeWriter($version);
        $actual = $writer($xml);

        self::assertXmlStringEqualsXmlString($expected, $actual);
    }

    public static function provideEnvelopeCases()
    {
        yield 'soap-1.1' => [
            SoapVersion::SOAP_11,
            '<Request>content</Request>',
            <<<EOXML
                <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/">
                    <soap:Body xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/">
                        <Request>content</Request>
                    </soap:Body>
                </soap:Envelope>
            EOXML
        ];

        yield 'soap-1.2' => [
            SoapVersion::SOAP_12,
            '<Request>content</Request>',
            <<<EOXML
                <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap12/">
                    <soap:Body xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap12/">
                        <Request>content</Request>
                    </soap:Body>
                </soap:Envelope>
            EOXML
        ];
    }
}
