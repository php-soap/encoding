<?php

declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Xml\Writer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Xml\Reader\OperationReader;
use Soap\Engine\Metadata\Model\MethodMeta;
use Soap\WsdlReader\Model\Definitions\BindingStyle;

#[CoversClass(OperationReader::class)]
final class OperationReaderTest extends TestCase
{
    /**
     *
     * @dataProvider provideEnvelopeCases
     */
    public function test_it_can_read_a_soap_envelope(MethodMeta $meta, string $envelope, array $expected): void
    {
        $reader = new OperationReader($meta);
        $actual = $reader($envelope);

        static::assertSame($expected, $actual);
    }

    public static function provideEnvelopeCases()
    {
        $methodMeta = (new MethodMeta())
            ->withTargetNamespace('http://tempuri.org/')
            ->withOperationName('Add');

        yield 'document-single-response-part' => [
            $methodMeta->withBindingStyle(BindingStyle::DOCUMENT->value),
            <<<EOXML
                <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/">
                    <soap:Body xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/">
                        <Request>content</Request>
                    </soap:Body>
                </soap:Envelope>
            EOXML,
            ['<Request>content</Request>'],
        ];

        yield 'rpc-document-single-response-part' => [
            $methodMeta->withBindingStyle(BindingStyle::RPC->value),
            <<<EOXML
                <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap12/">
                    <soap:Body xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap12/">
                        <tns:Add xmlns:tns="http://tempuri.org/">
                            <Request>content</Request>
                        </tns:Add>
                    </soap:Body>
                </soap:Envelope>
            EOXML,
            ['<Request>content</Request>'],
        ];

        yield 'rpc-document-no-response-part' => [
            $methodMeta->withBindingStyle(BindingStyle::RPC->value),
            <<<EOXML
                <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap12/">
                    <soap:Body xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap12/">
                        <tns:Add xmlns:tns="http://tempuri.org/"></tns:Add>
                    </soap:Body>
                </soap:Envelope>
            EOXML,
            [],
        ];

        yield 'rpc-document-multi-response-part' => [
            $methodMeta->withBindingStyle(BindingStyle::RPC->value),
            <<<EOXML
                <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap12/">
                    <soap:Body xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap12/">
                        <tns:Add xmlns:tns="http://tempuri.org/">
                            <a>a</a>
                            <b>b</b>
                            <c>c</c>
                        </tns:Add>
                    </soap:Body>
                </soap:Envelope>
            EOXML,
            [
                '<a>a</a>',
                '<b>b</b>',
                '<c>c</c>',
            ],
        ];
    }
}
