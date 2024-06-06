<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Fault\Encoder;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Fault\Encoder\Soap11FaultEncoder;
use Soap\Encoding\Fault\Soap11Fault;
use VeeWee\Xml\Dom\Document;
use function VeeWee\Xml\Dom\Configurator\loader;
use function VeeWee\Xml\Dom\Configurator\trim_spaces;
use function VeeWee\Xml\Dom\Loader\xml_string_loader;

#[CoversClass(Soap11FaultEncoder::class)]
#[CoversClass(Soap11Fault::class)]
final class Soap11FaultEncoderTest extends AbstractFaultEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => $encoder = new Soap11FaultEncoder(),
        ];

        yield 'required-fields-only' => [
            ...$baseConfig,
            'xml' => Document::configure(
                trim_spaces(),
                loader(xml_string_loader(
                    <<<EOXML
                    <env:Fault xmlns:env="http://schemas.xmlsoap.org/soap/envelope/">
                        <faultcode>a:Microsoft.Dynamics.ServiceBrokerException</faultcode>
                        <faultstring>Invalid input parameter x</faultstring>
                    </env:Fault>
                    EOXML
                ))
            )->stringifyDocumentElement(),
            'data' => new Soap11Fault(
                faultCode: 'a:Microsoft.Dynamics.ServiceBrokerException',
                faultString: 'Invalid input parameter x',
            ),
        ];
        yield 'all-fields' => [
            ...$baseConfig,
            'xml' => Document::configure(
                trim_spaces(),
                loader(xml_string_loader(
                    <<<EOXML
                    <env:Fault xmlns:env="http://schemas.xmlsoap.org/soap/envelope/">
                        <faultcode>a:Microsoft.Dynamics.ServiceBrokerException</faultcode>
                        <faultstring>Invalid input parameter x</faultstring>
                        <faultactor>uri:actor</faultactor>
                        <detail><element>value</element></detail>
                    </env:Fault>
                    EOXML
                ))
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
