<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Fault\Encoder;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Fault\Encoder\Soap12FaultEncoder;
use Soap\Encoding\Fault\Soap12Fault;
use VeeWee\Xml\Dom\Document;
use function VeeWee\Xml\Dom\Configurator\trim_spaces;

#[CoversClass(Soap12FaultEncoder::class)]
#[CoversClass(Soap12Fault::class)]
final class Soap12FaultEncoderTest extends AbstractFaultEncoderTests
{
    public function test_it_writes_the_exact_fault_bytes(): void
    {
        $actual = (new Soap12FaultEncoder())->iso()->to(new Soap12Fault(
            code: 'env:Sender',
            reason: 'a < b',
            subCode: 'm:Invalid',
            node: 'uri:node',
            role: 'uri:role',
            detail: '<env:Detail><element>value</element></env:Detail>',
        ));

        static::assertSame(
            '<env:Fault xmlns:env="http://www.w3.org/2003/05/soap-envelope"><env:Code><env:Value>env:Sender</env:Value><env:Subcode><env:Value>m:Invalid</env:Value></env:Subcode></env:Code><env:Reason><env:Text>a &lt; b</env:Text></env:Reason><env:Node>uri:node</env:Node><env:Role>uri:role</env:Role><env:Detail><element>value</element></env:Detail></env:Fault>',
            $actual
        );
    }

    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => $encoder = new Soap12FaultEncoder(),
        ];

        yield 'required-fields-only' => [
            ...$baseConfig,
            'xml' => Document::fromXmlString(
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
                ,
                trim_spaces()
            )->stringifyDocumentElement(),
            'data' => new Soap12Fault(
                code: 'env:Sender',
                reason: 'Sender Timeout',
            ),
        ];
        yield 'subcode-and-details-example' => [
            ...$baseConfig,
            'xml' => '<env:Fault xmlns:env="http://www.w3.org/2003/05/soap-envelope"><env:Code><env:Value>env:Sender</env:Value><env:Subcode><env:Value>m:MessageTimeout</env:Value></env:Subcode></env:Code><env:Reason><env:Text>Sender Timeout</env:Text></env:Reason><env:Detail xmlns:env="http://www.w3.org/2003/05/soap-envelope" xmlns:m="http://www.example.org/timeouts"><m:MaxTime>P5M</m:MaxTime></env:Detail></env:Fault>',
            'data' => new Soap12Fault(
                code: 'env:Sender',
                subCode: 'm:MessageTimeout',
                reason: 'Sender Timeout',
                detail: '<env:Detail xmlns:env="http://www.w3.org/2003/05/soap-envelope" xmlns:m="http://www.example.org/timeouts"><m:MaxTime>P5M</m:MaxTime></env:Detail>',
            ),
        ];
        yield 'full-example' => [
            ...$baseConfig,
            'xml' => '<env:Fault xmlns:env="http://www.w3.org/2003/05/soap-envelope"><env:Code><env:Value>env:Sender</env:Value><env:Subcode><env:Value>m:MessageTimeout</env:Value></env:Subcode></env:Code><env:Reason><env:Text>Sender Timeout</env:Text></env:Reason><env:Node>urn:node</env:Node><env:Role>urn:role</env:Role><env:Detail xmlns:env="http://www.w3.org/2003/05/soap-envelope" xmlns:m="http://www.example.org/timeouts"><m:MaxTime>P5M</m:MaxTime></env:Detail></env:Fault>',
            'data' => new Soap12Fault(
                code: 'env:Sender',
                subCode: 'm:MessageTimeout',
                reason: 'Sender Timeout',
                node: 'urn:node',
                role: 'urn:role',
                detail: '<env:Detail xmlns:env="http://www.w3.org/2003/05/soap-envelope" xmlns:m="http://www.example.org/timeouts"><m:MaxTime>P5M</m:MaxTime></env:Detail>',
            ),
        ];
    }
}
