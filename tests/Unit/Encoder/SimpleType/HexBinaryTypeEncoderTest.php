<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\SimpleType;


use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Encoder\SimpleType\HexBinaryTypeEncoder;
use Soap\Encoding\Test\Unit\Encoder\AbstractEncoderTests;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(HexBinaryTypeEncoder::class)]
class HexBinaryTypeEncoderTest extends AbstractEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => $encoder = new HexBinaryTypeEncoder(),
            'context' => $context = self::createContext(XsdType::guess('HexBinary')),
        ];

        yield 'simple' => [
            ...$baseConfig,
            'xml' => mb_strtoupper(bin2hex('hello')),
            'data' => 'hello',
        ];
    }
}
