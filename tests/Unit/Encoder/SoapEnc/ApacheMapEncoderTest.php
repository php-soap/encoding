<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\SoapEnc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Encoder\SoapEnc\ApacheMapEncoder;
use Soap\Encoding\Test\Unit\ContextCreatorTrait;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(ApacheMapEncoder::class)]
final class ApacheMapEncoderTest extends TestCase
{
    use ContextCreatorTrait;

    private const NAMESPACES = 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"'
        . ' xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" xmlns:apache="http://xml.apache.org/xml-soap"';

    #[DataProvider('provideDecodeCases')]
    public function test_it_decodes_values_by_their_xsi_information(string $items, array $expected): void
    {
        $context = self::createContext(XsdType::any());
        $xml = '<testParam ' . self::NAMESPACES . ' xsi:type="apache:Map">' . $items . '</testParam>';

        static::assertSame($expected, (new ApacheMapEncoder())->iso($context)->from($xml));
    }

    public static function provideDecodeCases(): iterable
    {
        yield 'scalar' => [
            '<item><key xsi:type="xsd:string">a</key><value xsi:type="xsd:int">1</value></item>',
            ['a' => 1],
        ];
        yield 'xsi-string' => [
            '<item><key>a</key><value xsi:type="xsd:string">1</value></item>',
            ['a' => '1'],
        ];
        yield 'nil' => [
            '<item><key>a</key><value xsi:nil="true"/></item>',
            ['a' => null],
        ];
        yield 'nested-map' => [
            '<item><key>a</key><value xsi:type="apache:Map"><item><key>b</key><value xsi:type="xsd:int">1</value></item></value></item>',
            ['a' => ['b' => 1]],
        ];
        yield 'nested-array' => [
            '<item><key>a</key><value xsi:type="SOAP-ENC:Array" SOAP-ENC:arrayType="xsd:int[2]"><item xsi:type="xsd:int">1</item><item xsi:type="xsd:int">2</item></value></item>',
            ['a' => [1, 2]],
        ];
    }
}
