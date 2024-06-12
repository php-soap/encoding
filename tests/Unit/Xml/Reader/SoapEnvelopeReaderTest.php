<?php

declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Xml\Writer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Exception\SoapFaultException;
use Soap\Encoding\Fault\Guard\SoapFaultGuard;
use Soap\Encoding\Xml\Reader\SoapEnvelopeReader;
use Soap\Encoding\Xml\Writer\SoapEnvelopeWriter;
use Soap\WsdlReader\Model\Definitions\SoapVersion;

#[CoversClass(SoapEnvelopeWriter::class)]
#[CoversClass(SoapFaultGuard::class)]
final class SoapEnvelopeReaderTest extends TestCase
{
    /**
     *
     * @dataProvider provideEnvelopeCases
     */
    public function test_it_can_read_a_soap_envelope(SoapVersion $version, string $envelope, string $expected): void
    {
        $reader = new SoapEnvelopeReader();
        $actual = $reader($envelope);

        static::assertXmlStringEqualsXmlString($expected, $actual->value());
    }

    #[Test]
    public function it_fails_reading_on_soap_12_fault(): void
    {
        $this->expectException(SoapFaultException::class);

        $reader = new SoapEnvelopeReader();
        $reader(<<<EOXML
            <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">
                <soap:Body xmlns:soap="http://www.w3.org/2003/05/soap-envelope">
                    <soap:Fault xmlns:env="http://www.w3.org/2003/05/soap-envelope">
                        <soap:Code>
                            <soap:Value>soap:Sender</soap:Value>
                        </soap:Code>
                        <soap:Reason>
                            <soap:Text>Sender Timeout</soap:Text>
                        </soap:Reason>
                    </soap:Fault>
                </soap:Body>
            </soap:Envelope>
        EOXML);

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
            <<<EOXML
            <soap:Body xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/">
                <Request>content</Request>
            </soap:Body>
            EOXML,
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
            <<<EOXML
                <soap:Body xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap12/">
                    <Request>content</Request>
                </soap:Body>
            EOXML,
        ];
    }
}
