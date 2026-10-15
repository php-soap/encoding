<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\SoapEnc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Encoder\SoapEnc\SoapObjectEncoder;
use Soap\Encoding\Test\Unit\ContextCreatorTrait;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(SoapObjectEncoder::class)]
final class SoapObjectEncoderTest extends TestCase
{
    use ContextCreatorTrait;

    private const NAMESPACES = 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"'
        . ' xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" xmlns:apache="http://xml.apache.org/xml-soap"';

    #[DataProvider('provideDecodeCases')]
    public function test_it_decodes_members_by_their_xsi_information(string $members, object $expected): void
    {
        $context = self::createContext(XsdType::any());
        $xml = '<testParam ' . self::NAMESPACES . ' xsi:type="SOAP-ENC:Struct">' . $members . '</testParam>';

        $actual = (new SoapObjectEncoder())->iso($context)->from($xml);

        static::assertSame(var_export($expected, true), var_export($actual, true));
    }

    public static function provideDecodeCases(): iterable
    {
        yield 'scalar' => [
            '<a xsi:type="xsd:int">1</a><b>x</b>',
            (object) ['a' => 1, 'b' => 'x'],
        ];
        yield 'xsi-string' => [
            '<a xsi:type="xsd:string">1</a>',
            (object) ['a' => '1'],
        ];
        yield 'nil' => [
            '<a xsi:nil="true"/>',
            (object) ['a' => null],
        ];
        yield 'nested-struct' => [
            '<a xsi:type="SOAP-ENC:Struct"><b xsi:type="xsd:int">1</b></a>',
            (object) ['a' => (object) ['b' => 1]],
        ];
        yield 'nested-map' => [
            '<a xsi:type="apache:Map"><item><key>b</key><value xsi:type="xsd:int">1</value></item></a>',
            (object) ['a' => ['b' => 1]],
        ];
    }
}
