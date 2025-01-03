<?php declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\Method;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Encoder\Method\MethodContext;
use Soap\Encoding\Encoder\Method\ResponseEncoder;
use Soap\Encoding\EncoderRegistry;
use Soap\Engine\Metadata\Collection\MethodCollection;
use Soap\Engine\Metadata\Collection\ParameterCollection;
use Soap\Engine\Metadata\Collection\TypeCollection;
use Soap\Engine\Metadata\InMemoryMetadata;
use Soap\Engine\Metadata\Model\Method;
use Soap\Engine\Metadata\Model\MethodMeta;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\WsdlReader\Model\Definitions\BindingStyle;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Model\Definitions\Namespaces;
use Soap\WsdlReader\Model\Definitions\SoapVersion;
use Soap\Xml\Xmlns;

#[CoversClass(ResponseEncoder::class)]
final class ResponseEncoderTest extends AbstractMethodEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => new ResponseEncoder(),
        ];

        yield 'one-way' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withIsOneWay(true)
            ),
            'xml' => '',
            'data' => [],
        ];

        yield 'soap11-document-literal-no-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withOutputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"/></SOAP-ENV:Envelope>

            EOXML,
            'data' => [],
        ];
        yield 'soap11-document-encoded-no-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withOutputBindingUsage(BindingUse::ENCODED->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"/></SOAP-ENV:Envelope>

            EOXML,
            'data' => [],
        ];
        yield 'soap11-rpc-literal-no-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withOutputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><tns:foo xmlns:tns="uri:tns"/></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => [],
        ];
        yield 'soap11-rpc-encoded-no-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withOutputBindingUsage(BindingUse::ENCODED->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><tns:foo xmlns:tns="uri:tns"/></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => [],
        ];
        yield 'soap12-document-literal-no-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withOutputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"/></SOAP-ENV:Envelope>

            EOXML,
            'data' => [],
        ];
        yield 'soap12-document-encoded-no-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withOutputBindingUsage(BindingUse::ENCODED->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"/></SOAP-ENV:Envelope>

            EOXML,
            'data' => [],
        ];
        yield 'soap12-rpc-literal-no-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withOutputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><tns:foo xmlns:tns="uri:tns"/></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => [],
        ];
        yield 'soap12-rpc-encoded-no-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withOutputBindingUsage(BindingUse::ENCODED->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><tns:foo xmlns:tns="uri:tns"/></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => [],
        ];
        yield 'soap11-document-literal-single-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withOutputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><xsd:string xmlns:xsd="http://www.w3.org/2001/XMLSchema">hello</xsd:string></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello'],
        ];
        yield 'soap11-document-encoded-single-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withOutputBindingUsage(BindingUse::ENCODED->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><xsd:string xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">hello</xsd:string></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello'],
        ];
        yield 'soap11-rpc-literal-single-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withOutputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><tns:foo xmlns:tns="uri:tns"><return>hello</return></tns:foo></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello'],
        ];
        yield 'soap11-rpc-encoded-single-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withOutputBindingUsage(BindingUse::ENCODED->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><tns:foo xmlns:tns="uri:tns"><return xmlns:xsd="http://www.w3.org/2001/XMLSchema" xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">hello</return></tns:foo></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello'],
        ];
        yield 'soap12-document-literal-single-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withOutputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><xsd:string xmlns:xsd="http://www.w3.org/2001/XMLSchema">hello</xsd:string></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello'],
        ];
        yield 'soap12-document-encoded-single-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withOutputBindingUsage(BindingUse::ENCODED->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><xsd:string xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">hello</xsd:string></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello'],
        ];
        yield 'soap12-rpc-literal-single-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withOutputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><tns:foo xmlns:tns="uri:tns"><return>hello</return></tns:foo></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello'],
        ];
        yield 'soap12-rpc-encoded-single-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withOutputBindingUsage(BindingUse::ENCODED->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><tns:foo xmlns:tns="uri:tns"><return xmlns:xsd="http://www.w3.org/2001/XMLSchema" xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">hello</return></tns:foo></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello'],
        ];
        yield 'soap11-document-literal-multi-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withOutputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><xsd:string xmlns:xsd="http://www.w3.org/2001/XMLSchema">hello</xsd:string><xsd:string xmlns:xsd="http://www.w3.org/2001/XMLSchema">world</xsd:string></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello', 'world'],
        ];
        yield 'soap11-document-encoded-multi-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withOutputBindingUsage(BindingUse::ENCODED->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><xsd:string xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">hello</xsd:string><xsd:string xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">world</xsd:string></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello', 'world'],
        ];
        yield 'soap11-rpc-literal-multi-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withOutputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><tns:foo xmlns:tns="uri:tns"><return>hello</return><return>world</return></tns:foo></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello', 'world'],
        ];
        yield 'soap11-rpc-encoded-multi-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withOutputBindingUsage(BindingUse::ENCODED->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><tns:foo xmlns:tns="uri:tns"><return xmlns:xsd="http://www.w3.org/2001/XMLSchema" xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">hello</return><return xmlns:xsd="http://www.w3.org/2001/XMLSchema" xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">world</return></tns:foo></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello', 'world'],
        ];
        yield 'soap12-document-literal-multi-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withOutputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><xsd:string xmlns:xsd="http://www.w3.org/2001/XMLSchema">hello</xsd:string><xsd:string xmlns:xsd="http://www.w3.org/2001/XMLSchema">world</xsd:string></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello', 'world'],
        ];
        yield 'soap12-document-encoded-multi-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withOutputBindingUsage(BindingUse::ENCODED->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><xsd:string xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">hello</xsd:string><xsd:string xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">world</xsd:string></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello', 'world'],
        ];
        yield 'soap12-rpc-literal-multi-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withOutputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><tns:foo xmlns:tns="uri:tns"><return>hello</return><return>world</return></tns:foo></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello', 'world'],
        ];
        yield 'soap12-rpc-encoded-multi-result' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                self::createType('return'),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withOutputBindingUsage(BindingUse::ENCODED->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><tns:foo xmlns:tns="uri:tns"><return xmlns:xsd="http://www.w3.org/2001/XMLSchema" xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">hello</return><return xmlns:xsd="http://www.w3.org/2001/XMLSchema" xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">world</return></tns:foo></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello', 'world'],
        ];
    }

    private static function createMethodContext(
        XsdType $returnType,
        MethodMeta $meta
    ): MethodContext {
        $method = (new Method(
            'foo',
            new ParameterCollection(),
            $returnType
        ))->withMeta(
            static fn (): MethodMeta => $meta
                ->withTargetNamespace('uri:tns')
                ->withOperationName('foo')
        );

        return new MethodContext(
            $method,
            new InMemoryMetadata(new TypeCollection(), new MethodCollection($method)),
            EncoderRegistry::default(),
            new Namespaces(
                [
                    'tns' => 'uri:tns',
                    'xsd' => Xmlns::xsd()->value(),
                ],
                [
                    'uri:tns' => 'tns',
                    Xmlns::xsd()->value() => 'xsd',
                ],
            )
        );
    }
}
