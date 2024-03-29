<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\SimpleType;


use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Encoder\SimpleType\StringTypeEncoder;
use Soap\Encoding\Test\Unit\Encoder\AbstractEncoderTests;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(StringTypeEncoder::class)]
class StringTypeEncoderTest extends AbstractEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => $encoder = new StringTypeEncoder(),
            'context' => $context = self::createContext(XsdType::guess('string')),
        ];

        yield 'simple' => [
            ...$baseConfig,
            'xml' => 'hello',
            'data' => 'hello',
        ];
        yield 'special-chars' => [
            ...$baseConfig,
            'xml' => 'hëllo\'"<>',
            'data' => 'hëllo\'"<>',
        ];
    }
}
