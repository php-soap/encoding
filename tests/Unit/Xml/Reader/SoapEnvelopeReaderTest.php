<?php

declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Xml\Writer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Xml\Reader\SoapEnvelopeReader;
use Soap\Encoding\Xml\Writer\SoapEnvelopeWriter;
use Soap\WsdlReader\Model\Definitions\SoapVersion;

#[CoversClass(SoapEnvelopeWriter::class)]
class SoapEnvelopeReaderTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideEnvelopeCases
     */
    public function it_can_read_a_soap_envelope(SoapVersion $version, string $envelope, string $expected): void
    {
        $reader = new SoapEnvelopeReader();
        $actual = $reader($envelope);

        self::assertXmlStringEqualsXmlString($expected, $actual);
    }

    public static function provideEnvelopeCases()
    {
        yield 'soap-1.1' => [
            SoapVersion::SOAP_11,
            <<<EOXML
                <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/">
                    <soap:Body xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/">
                        <Request>content</Request>
                    </soap:Body>
                </soap:Envelope>
            EOXML,
            '<Request>content</Request>',
        ];

        yield 'soap-1.2' => [
            SoapVersion::SOAP_12,
            <<<EOXML
                <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap12/">
                    <soap:Body xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap12/">
                        <Request>content</Request>
                    </soap:Body>
                </soap:Envelope>
            EOXML,
            '<Request>content</Request>',
        ];
    }
}
