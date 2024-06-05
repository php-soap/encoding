<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\SimpleType;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Encoder\SimpleType\IntTypeEncoder;
use Soap\Encoding\Test\Unit\Encoder\AbstractEncoderTests;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(IntTypeEncoder::class)]
final class IntTypeEncoderTest extends AbstractEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => $encoder = new IntTypeEncoder(),
            'context' => $context = self::createContext(XsdType::guess('int')),
        ];

        yield 'simple' => [
            ...$baseConfig,
            'xml' => '123',
            'data' => 123,
        ];
    }
}
