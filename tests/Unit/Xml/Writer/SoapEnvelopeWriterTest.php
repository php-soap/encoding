<?php

declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Xml\Writer;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psl\Option\Option;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\EncoderRegistry;
use Soap\Encoding\Exception\EncodingException;
use Soap\Encoding\Exception\ExceptionInterface;
use Soap\Encoding\Xml\Writer\ParameterBuilder;
use Soap\Encoding\Xml\Writer\SoapEnvelopeWriter;
use Soap\Engine\Metadata\Collection\MethodCollection;
use Soap\Engine\Metadata\Collection\TypeCollection;
use Soap\Engine\Metadata\InMemoryMetadata;
use Soap\Engine\Metadata\Model\MethodMeta;
use Soap\Engine\Metadata\Model\TypeMeta;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\WsdlReader\Model\Definitions\BindingStyle;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Model\Definitions\EncodingStyle;
use Soap\WsdlReader\Model\Definitions\Namespaces;
use Soap\WsdlReader\Model\Definitions\SoapVersion;
use ValueError;
use VeeWee\Xml\Exception\RuntimeException as XmlRuntimeException;
use function Psl\Option\none;
use function Psl\Option\some;
use function VeeWee\Xml\Writer\Builder\raw;

#[CoversClass(SoapEnvelopeWriter::class)]
final class SoapEnvelopeWriterTest extends TestCase
{
    #[DataProvider('provideEnvelopeCases')]
    public function test_it_can_write_a_soap_envelope(
        SoapVersion $version,
        BindingUse $bindingUse,
        Option $encodingStyle,
        string $xml,
        string $expected
    ): void {
        $writer = new SoapEnvelopeWriter($version, $bindingUse, $encodingStyle, raw($xml));
        $actual = $writer();

        static::assertXmlStringEqualsXmlString($expected, $actual);
    }

    public function test_it_writes_the_exact_envelope_bytes(): void
    {
        $writer = new SoapEnvelopeWriter(
            SoapVersion::SOAP_11,
            BindingUse::ENCODED,
            some(EncodingStyle::SOAP_11),
            raw('<Request>content</Request>')
        );

        static::assertSame(
            '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<SOAP-ENV:Body xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<Request>content</Request>'
            . '</SOAP-ENV:Body></SOAP-ENV:Envelope>',
            $writer()
        );
    }

    public function test_it_can_fail_writing_with_encoding_exception(): void
    {
        $this->expectException(ExceptionInterface::class);

        $writer = new SoapEnvelopeWriter(
            SoapVersion::SOAP_11,
            BindingUse::LITERAL,
            some(EncodingStyle::SOAP_11),
            static function () {
                throw EncodingException::encodingValue('Oops', XsdType::any(), new Exception('previous'));
                yield;
            }
        );
        $writer();
    }

    public function test_it_surfaces_an_element_write_failure_as_the_encoding_exception(): void
    {
        $context = new Context(
            XsdType::guess('string')
                ->withXmlTargetNodeName('1st')
                ->withMeta(static fn (TypeMeta $meta) => $meta->withIsSimple(true)->withIsElement(true)),
            new InMemoryMetadata(new TypeCollection(), new MethodCollection()),
            EncoderRegistry::default(),
            new Namespaces([], [])
        );
        $parameter = new ParameterBuilder(
            (new MethodMeta())->withBindingStyle(BindingStyle::RPC->value),
            $context,
            'hello'
        );
        $writer = new SoapEnvelopeWriter(SoapVersion::SOAP_11, BindingUse::LITERAL, none(), $parameter(...));

        try {
            $writer();
        } catch (EncodingException $exception) {
            $message = 'XMLWriter::startElement(): Argument #2 must be a valid element name, "1st" given';

            $previous = $exception->getPrevious();
            static::assertInstanceOf(XmlRuntimeException::class, $previous);
            static::assertSame($message . PHP_EOL, $previous->getMessage());

            $previous = $previous->getPrevious();
            static::assertInstanceOf(ValueError::class, $previous);
            static::assertSame($message, $previous->getMessage());

            return;
        }

        static::fail('Expected an EncodingException.');
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
