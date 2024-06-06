<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Fault\Encoder;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Fault\Encoder\Soap12FaultEncoder;
use Soap\Encoding\Fault\Soap12Fault;
use VeeWee\Xml\Dom\Document;
use function VeeWee\Xml\Dom\Configurator\loader;
use function VeeWee\Xml\Dom\Configurator\trim_spaces;
use function VeeWee\Xml\Dom\Loader\xml_string_loader;

#[CoversClass(Soap12FaultEncoder::class)]
#[CoversClass(Soap12Fault::class)]
final class Soap12FaultEncoderTest extends AbstractFaultEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => $encoder = new Soap12FaultEncoder(),
        ];

        yield 'required-fields-only' => [
            ...$baseConfig,
            'xml' => Document::configure(
                trim_spaces(),
                loader(xml_string_loader(
                    <<<EOXML
                    <env:Fault xmlns:env="http://www.w3.org/2003/05/soap-envelope">
                        <env:Code>
                            <env:Value>env:Sender</env:Value>
                        </env:Code>
                        <env:Reason>
                            <env:Text>Sender Timeout</env:Text>
                        </env:Reason>
                    </env:Fault>
                    EOXML
                ))
            )->stringifyDocumentElement(),
            'data' => new Soap12Fault(
                code: 'env:Sender',
                reason: 'Sender Timeout',
            ),
        ];
        yield 'subcode-and-details-example' => [
            ...$baseConfig,
            'xml' => Document::configure(
                trim_spaces(),
                loader(xml_string_loader(
                    <<<EOXML
                    <env:Fault xmlns:env="http://www.w3.org/2003/05/soap-envelope">
                        <env:Code>
                            <env:Value>env:Sender</env:Value>
                            <env:Subcode>
                                <env:Value>m:MessageTimeout</env:Value>
                            </env:Subcode>
                        </env:Code>
                        <env:Reason>
                            <env:Text>Sender Timeout</env:Text>
                        </env:Reason>
                        <env:Detail xmlns:m="http://www.example.org/timeouts" xmlns:env="http://www.w3.org/2003/05/soap-envelope"><m:MaxTime>P5M</m:MaxTime></env:Detail>
                    </env:Fault>
                    EOXML
                ))
            )->stringifyDocumentElement(),
            'data' => new Soap12Fault(
                code: 'env:Sender',
                subCode: 'm:MessageTimeout',
                reason: 'Sender Timeout',
                detail: trim(<<<EOXML
                <env:Detail xmlns:m="http://www.example.org/timeouts" xmlns:env="http://www.w3.org/2003/05/soap-envelope"><m:MaxTime>P5M</m:MaxTime></env:Detail>
                EOXML)
            ),
        ];
        yield 'full-example' => [
            ...$baseConfig,
            'xml' => Document::configure(
                trim_spaces(),
                loader(xml_string_loader(
                    <<<EOXML
                    <env:Fault xmlns:env="http://www.w3.org/2003/05/soap-envelope">
                        <env:Code>
                            <env:Value>env:Sender</env:Value>
                            <env:Subcode>
                                <env:Value>m:MessageTimeout</env:Value>
                            </env:Subcode>
                        </env:Code>
                        <env:Reason>
                            <env:Text>Sender Timeout</env:Text>
                        </env:Reason>
                        <env:Node>urn:node</env:Node>
                        <env:Role>urn:role</env:Role>
                        <env:Detail xmlns:m="http://www.example.org/timeouts" xmlns:env="http://www.w3.org/2003/05/soap-envelope"><m:MaxTime>P5M</m:MaxTime></env:Detail>
                    </env:Fault>
                    EOXML
                ))
            )->stringifyDocumentElement(),
            'data' => new Soap12Fault(
                code: 'env:Sender',
                subCode: 'm:MessageTimeout',
                reason: 'Sender Timeout',
                node: 'urn:node',
                role: 'urn:role',
                detail: trim(<<<EOXML
                <env:Detail xmlns:m="http://www.example.org/timeouts" xmlns:env="http://www.w3.org/2003/05/soap-envelope"><m:MaxTime>P5M</m:MaxTime></env:Detail>
                EOXML)
            ),
        ];
    }
}
