<?php declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\Method;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Encoder\Method\MethodContext;
use Soap\Encoding\Encoder\Method\SoapMethodEncoder;
use Soap\Engine\Metadata\Model\Parameter;
use Soap\Engine\Metadata\Model\TypeMeta;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\Xml\Xmlns;

abstract class AbstractMethodEncoderTests extends TestCase
{
    /**
     * @return iterable<int, array{encoder: SoapMethodEncoder, context: MethodContext, xml: string, data: mixed}>
     */
    abstract public static function provideIsomorphicCases(): iterable;

    #[DataProvider('provideIsomorphicCases')]
    public function test_it_can_decode_from_xml(SoapMethodEncoder $encoder, MethodContext $context, string $xml, mixed $data): void
    {
        $iso = $encoder->iso($context);
        $actual = $iso->from($xml);

        static::assertEquals($data, $actual);
    }

    #[DataProvider('provideIsomorphicCases')]
    public function test_it_can_encode_into_xml(SoapMethodEncoder $encoder, MethodContext $context, string $xml, mixed $data): void
    {
        $iso = $encoder->iso($context);
        $actual = $iso->to($data);

        static::assertSame($xml, $actual);
    }

    protected static function createParameter(string $name): Parameter
    {
        return new Parameter(
            $name,
            self::createType($name),
        );
    }

    protected static function createType(string $name): XsdType
    {
        return XsdType::guess('string')
            ->withXmlTypeName('string')
            ->withXmlNamespace(Xmlns::xsd()->value())
            ->withXmlNamespaceName('xsd')
            ->withXmlTargetNodeName($name)
            ->withXmlTargetNamespace(Xmlns::xsd()->value())
            ->withXmlTargetNamespaceName('xsd')
            ->withMeta(
                static fn (): TypeMeta => (new TypeMeta())
                    ->withIsElement(true)
                    ->withIsSimple(true)
            );
    }
}
