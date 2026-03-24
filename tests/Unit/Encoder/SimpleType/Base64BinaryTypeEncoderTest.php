<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\SimpleType;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Soap\Encoding\Encoder\SimpleType\Base64BinaryTypeEncoder;
use Soap\Encoding\Test\Unit\Encoder\AbstractEncoderTests;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(Base64BinaryTypeEncoder::class)]
final class Base64BinaryTypeEncoderTest extends AbstractEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => $encoder = new Base64BinaryTypeEncoder(),
            'context' => $context = self::createContext(XsdType::guess('base64Binary')),
        ];

        yield 'simple' => [
            ...$baseConfig,
            'xml' => base64_encode('hello'),
            'data' => 'hello',
        ];
    }

    /**
     * @return iterable<string, array{xml: string, data: string}>
     */
    public static function provideBase64WithWhitespaceCases(): iterable
    {
        $data = 'hello world, this is a longer string for base64 wrapping';
        $encoded = base64_encode($data);

        yield 'with-line-breaks' => [
            'xml' => chunk_split($encoded, 76, "\r\n"),
            'data' => $data,
        ];

        yield 'with-spaces' => [
            'xml' => chunk_split($encoded, 20, " "),
            'data' => $data,
        ];

        yield 'with-mixed-whitespace' => [
            'xml' => chunk_split($encoded, 30, "\n\t"),
            'data' => $data,
        ];

        yield 'without-padding' => [
            'xml' => rtrim(base64_encode('hello world'), '='),
            'data' => 'hello world',
        ];
    }

    #[DataProvider('provideBase64WithWhitespaceCases')]
    public function test_it_can_decode_base64_with_whitespace(string $xml, string $data): void
    {
        $encoder = new Base64BinaryTypeEncoder();
        $context = self::createContext(XsdType::guess('base64Binary'));
        $iso = $encoder->iso($context);

        static::assertEquals($data, $iso->from($xml));
    }
}
