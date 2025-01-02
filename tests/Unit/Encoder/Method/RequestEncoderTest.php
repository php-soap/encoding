<?php declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\Method;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Encoder\Method\MethodContext;
use Soap\Encoding\Encoder\Method\RequestEncoder;
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

#[CoversClass(RequestEncoder::class)]
final class RequestEncoderTest extends AbstractMethodEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => new RequestEncoder(),
        ];

        yield 'soap11-document-literal-no-args' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withInputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"/></SOAP-ENV:Envelope>

            EOXML,
            'data' => [],
        ];
        yield 'soap11-document-encoded-no-args' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withInputBindingUsage(BindingUse::ENCODED->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"/></SOAP-ENV:Envelope>

            EOXML,
            'data' => [],
        ];
        yield 'soap11-rpc-literal-no-args' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withInputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><tns:foo xmlns:tns="uri:tns"/></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => [],
        ];
        yield 'soap11-rpc-encoded-no-args' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withInputBindingUsage(BindingUse::ENCODED->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><tns:foo xmlns:tns="uri:tns"/></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => [],
        ];
        yield 'soap12-document-literal-no-args' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withInputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"/></SOAP-ENV:Envelope>

            EOXML,
            'data' => [],
        ];
        yield 'soap12-document-encoded-no-args' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withInputBindingUsage(BindingUse::ENCODED->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"/></SOAP-ENV:Envelope>

            EOXML,
            'data' => [],
        ];
        yield 'soap12-literal-rpc-no-args' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withInputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><tns:foo xmlns:tns="uri:tns"/></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => [],
        ];
        yield 'soap12-encoded-rpc-no-args' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withInputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><tns:foo xmlns:tns="uri:tns"/></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => [],
        ];
        yield 'soap11-document-literal-single-arg' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(self::createParameter('arg1')),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withInputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><xsd:string xmlns:xsd="http://www.w3.org/2001/XMLSchema">hello</xsd:string></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello'],
        ];
        yield 'soap11-document-encoded-single-arg' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(self::createParameter('arg1')),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withInputBindingUsage(BindingUse::ENCODED->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><xsd:string xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">hello</xsd:string></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello'],
        ];
        yield 'soap11-rpc-literal-single-arg' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(self::createParameter('arg1')),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withInputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><tns:foo xmlns:tns="uri:tns"><arg1>hello</arg1></tns:foo></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello'],
        ];
        yield 'soap11-rpc-encoded-single-arg' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(self::createParameter('arg1')),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withInputBindingUsage(BindingUse::ENCODED->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><tns:foo xmlns:tns="uri:tns"><arg1 xmlns:xsd="http://www.w3.org/2001/XMLSchema" xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">hello</arg1></tns:foo></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello'],
        ];
        yield 'soap12-document-literal-single-arg' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(self::createParameter('arg1')),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withInputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><xsd:string xmlns:xsd="http://www.w3.org/2001/XMLSchema">hello</xsd:string></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello'],
        ];
        yield 'soap12-document-encoded-single-arg' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(self::createParameter('arg1')),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withInputBindingUsage(BindingUse::ENCODED->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><xsd:string xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">hello</xsd:string></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello'],
        ];
        yield 'soap12-rpc-literal-single-arg' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(self::createParameter('arg1')),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withInputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><tns:foo xmlns:tns="uri:tns"><arg1>hello</arg1></tns:foo></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello'],
        ];
        yield 'soap12-rpc-encoded-single-arg' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(self::createParameter('arg1')),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withInputBindingUsage(BindingUse::ENCODED->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><tns:foo xmlns:tns="uri:tns"><arg1 xmlns:xsd="http://www.w3.org/2001/XMLSchema" xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">hello</arg1></tns:foo></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello'],
        ];
        yield 'soap11-document-literal-multi-arg' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(
                    self::createParameter('arg1'),
                    self::createParameter('arg2'),
                ),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withInputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><xsd:string xmlns:xsd="http://www.w3.org/2001/XMLSchema">hello</xsd:string><xsd:string xmlns:xsd="http://www.w3.org/2001/XMLSchema">world</xsd:string></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello', 'world'],
        ];
        yield 'soap11-document-encoded-multi-arg' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(
                    self::createParameter('arg1'),
                    self::createParameter('arg2'),
                ),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withInputBindingUsage(BindingUse::ENCODED->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><xsd:string xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">hello</xsd:string><xsd:string xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">world</xsd:string></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello', 'world'],
        ];
        yield 'soap11-rpc-literal-multi-arg' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(
                    self::createParameter('arg1'),
                    self::createParameter('arg2'),
                ),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withInputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><tns:foo xmlns:tns="uri:tns"><arg1>hello</arg1><arg2>world</arg2></tns:foo></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello', 'world'],
        ];
        yield 'soap11-rpc-encoded-multi-arg' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(
                    self::createParameter('arg1'),
                    self::createParameter('arg2'),
                ),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_11->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withInputBindingUsage(BindingUse::ENCODED->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><tns:foo xmlns:tns="uri:tns"><arg1 xmlns:xsd="http://www.w3.org/2001/XMLSchema" xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">hello</arg1><arg2 xmlns:xsd="http://www.w3.org/2001/XMLSchema" xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">world</arg2></tns:foo></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello', 'world'],
        ];
        yield 'soap12-document-literal-multi-arg' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(
                    self::createParameter('arg1'),
                    self::createParameter('arg2'),
                ),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withInputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><xsd:string xmlns:xsd="http://www.w3.org/2001/XMLSchema">hello</xsd:string><xsd:string xmlns:xsd="http://www.w3.org/2001/XMLSchema">world</xsd:string></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello', 'world'],
        ];
        yield 'soap12-document-encoded-multi-arg' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(
                    self::createParameter('arg1'),
                    self::createParameter('arg2'),
                ),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::DOCUMENT->value)
                    ->withInputBindingUsage(BindingUse::ENCODED->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><xsd:string xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">hello</xsd:string><xsd:string xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">world</xsd:string></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello', 'world'],
        ];
        yield 'soap12-rpc-literal-multi-arg' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(
                    self::createParameter('arg1'),
                    self::createParameter('arg2'),
                ),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withInputBindingUsage(BindingUse::LITERAL->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><tns:foo xmlns:tns="uri:tns"><arg1>hello</arg1><arg2>world</arg2></tns:foo></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello', 'world'],
        ];
        yield 'soap12-rpc-encoded-multi-arg' => [
            ...$baseConfig,
            'context' => self::createMethodContext(
                new ParameterCollection(
                    self::createParameter('arg1'),
                    self::createParameter('arg2'),
                ),
                (new MethodMeta())
                    ->withSoapVersion(SoapVersion::SOAP_12->value)
                    ->withBindingStyle(BindingStyle::RPC->value)
                    ->withInputBindingUsage(BindingUse::ENCODED->value)
            ),
            'xml' => <<<EOXML
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><tns:foo xmlns:tns="uri:tns"><arg1 xmlns:xsd="http://www.w3.org/2001/XMLSchema" xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">hello</arg1><arg2 xmlns:xsd="http://www.w3.org/2001/XMLSchema" xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">world</arg2></tns:foo></SOAP-ENV:Body></SOAP-ENV:Envelope>

            EOXML,
            'data' => ['hello', 'world'],
        ];
    }

    private static function createMethodContext(
        ParameterCollection $params,
        MethodMeta $meta
    ): MethodContext {
        $method = (new Method(
            'foo',
            $params,
            XsdType::guess('string'),
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
