<?php

declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Xml\Writer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psl\Option\Option;
use Soap\Encoding\Xml\Writer\SoapEnvelopeWriter;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Model\Definitions\EncodingStyle;
use Soap\WsdlReader\Model\Definitions\SoapVersion;
use function Psl\Option\none;
use function Psl\Option\some;
use function VeeWee\Xml\Writer\Builder\raw;

#[CoversClass(SoapEnvelopeWriter::class)]
class SoapEnvelopeWriterTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideEnvelopeCases
     */
    public function it_can_write_a_soap_envelope(
        SoapVersion $version,
        BindingUse $bindingUse,
        Option $encodingStyle,
        string $xml,
        string $expected
    ): void{
        $writer = new SoapEnvelopeWriter($version, $bindingUse, $encodingStyle, raw($xml));
        $actual = $writer();

        self::assertXmlStringEqualsXmlString($expected, $actual);
    }

    public static function provideEnvelopeCases()
    {
        yield 'soap-1.1-literal' => [
            SoapVersion::SOAP_11,
            BindingUse::LITERAL,
            none(),
            '<Request>content</Request>',
            <<<EOXML
                <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
                    <SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
                        <Request>content</Request>
                    </SOAP-ENV:Body>
                </SOAP-ENV:Envelope>
            EOXML
        ];

        yield 'soap-1.2-literal' => [
            SoapVersion::SOAP_12,
            BindingUse::LITERAL,
            none(),
            '<Request>content</Request>',
            <<<EOXML
                <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope">
                    <SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope">
                        <Request>content</Request>
                    </SOAP-ENV:Body>
                </SOAP-ENV:Envelope>
            EOXML
        ];

        yield 'soap-1.1-encoded' => [
            SoapVersion::SOAP_11,
            BindingUse::ENCODED,
            some(EncodingStyle::SOAP_11),
            '<Request>content</Request>',
            <<<EOXML
                <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
                    <SOAP-ENV:Body xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                        <Request>content</Request>
                    </SOAP-ENV:Body>
                </SOAP-ENV:Envelope>
            EOXML
        ];

        yield 'soap-1.2-encoded' => [
            SoapVersion::SOAP_12,
            BindingUse::ENCODED,
            some(EncodingStyle::SOAP_12_2001_12),
            '<Request>content</Request>',
            <<<EOXML
                <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope">
                    <SOAP-ENV:Body xmlns:SOAP-ENC="http://www.w3.org/2001/12/soap-encoding" SOAP-ENV:encodingStyle="http://www.w3.org/2001/12/soap-encoding">
                        <Request>content</Request>
                    </SOAP-ENV:Body>
                </SOAP-ENV:Envelope>
            EOXML
        ];
    }
}
