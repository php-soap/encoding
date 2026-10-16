<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Fault\Encoder;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Fault\Encoder\Soap11FaultEncoder;
use Soap\Encoding\Fault\Soap11Fault;
use VeeWee\Xml\Dom\Document;
use function VeeWee\Xml\Dom\Configurator\trim_spaces;

#[CoversClass(Soap11FaultEncoder::class)]
#[CoversClass(Soap11Fault::class)]
final class Soap11FaultEncoderTest extends AbstractFaultEncoderTests
{
    public function test_it_writes_the_exact_fault_bytes(): void
    {
        $actual = (new Soap11FaultEncoder())->iso()->to(new Soap11Fault(
            faultCode: 'env:Server',
            faultString: 'a < b',
            faultActor: 'uri:actor',
            detail: '<detail><element>value</element></detail>',
        ));

        static::assertSame(
            '<env:Fault xmlns:env="http://schemas.xmlsoap.org/soap/envelope/"><faultcode>env:Server</faultcode><faultstring>a &lt; b</faultstring><faultactor>uri:actor</faultactor><detail><element>value</element></detail></env:Fault>',
            $actual
        );
    }

    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => $encoder = new Soap11FaultEncoder(),
        ];

        yield 'required-fields-only' => [
            ...$baseConfig,
            'xml' => Document::fromXmlString(
                <<<EOXML
                    <env:Fault xmlns:env="http://schemas.xmlsoap.org/soap/envelope/">
                        <faultcode>a:Microsoft.Dynamics.ServiceBrokerException</faultcode>
                        <faultstring>Invalid input parameter x</faultstring>
                    </env:Fault>
                    EOXML
                ,
                trim_spaces()
            )->stringifyDocumentElement(),
            'data' => new Soap11Fault(
                faultCode: 'a:Microsoft.Dynamics.ServiceBrokerException',
                faultString: 'Invalid input parameter x',
            ),
        ];
        yield 'all-fields' => [
            ...$baseConfig,
            'xml' => Document::fromXmlString(
                <<<EOXML
                    <env:Fault xmlns:env="http://schemas.xmlsoap.org/soap/envelope/">
                        <faultcode>a:Microsoft.Dynamics.ServiceBrokerException</faultcode>
                        <faultstring>Invalid input parameter x</faultstring>
                        <faultactor>uri:actor</faultactor>
                        <detail><element>value</element></detail>
                    </env:Fault>
                    EOXML
                ,
                trim_spaces()
            )->stringifyDocumentElement(),
            'data' => new Soap11Fault(
                faultCode: 'a:Microsoft.Dynamics.ServiceBrokerException',
                faultString: 'Invalid input parameter x',
                faultActor: 'uri:actor',
                detail: '<detail><element>value</element></detail>',
            ),
        ];
    }
}
