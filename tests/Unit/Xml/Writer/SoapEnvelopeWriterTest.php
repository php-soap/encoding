<?php

declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Xml\Writer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Xml\Writer\SoapEnvelopeWriter;
use Soap\WsdlReader\Model\Definitions\Binding;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Model\Definitions\SoapVersion;
use function VeeWee\Xml\Writer\Builder\raw;

#[CoversClass(SoapEnvelopeWriter::class)]
class SoapEnvelopeWriterTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideEnvelopeCases
     */
    public function it_can_write_a_soap_envelope(SoapVersion $version, BindingUse $bindingUse, string $xml, string $expected): void
    {
        $writer = new SoapEnvelopeWriter($version, $bindingUse, raw($xml));
        $actual = $writer();

        self::assertXmlStringEqualsXmlString($expected, $actual);
    }

    public static function provideEnvelopeCases()
    {
        yield 'soap-1.1' => [
            SoapVersion::SOAP_11,
            BindingUse::LITERAL,
            '<Request>content</Request>',
            <<<EOXML
                <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
                    <SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
                        <Request>content</Request>
                    </SOAP-ENV:Body>
                </SOAP-ENV:Envelope>
            EOXML
        ];

        yield 'soap-1.2' => [
            SoapVersion::SOAP_12,
            BindingUse::LITERAL,
            '<Request>content</Request>',
            <<<EOXML
                <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope">
                    <SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope">
                        <Request>content</Request>
                    </SOAP-ENV:Body>
                </SOAP-ENV:Envelope>
            EOXML
        ];
    }
}
