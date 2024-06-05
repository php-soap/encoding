<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\SimpleType;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Encoder\SimpleType\BoolTypeEncoder;
use Soap\Encoding\Test\Unit\Encoder\AbstractEncoderTests;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(BoolTypeEncoder::class)]
final class BoolTypeEncoderTest extends AbstractEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => $encoder = new BoolTypeEncoder(),
            'context' => $context = self::createContext(XsdType::guess('bool')),
        ];

        yield 'true' => [
            ...$baseConfig,
            'xml' => 'true',
            'data' => true,
        ];
        yield 'false' => [
            ...$baseConfig,
            'xml' => 'false',
            'data' => false,
        ];
    }
}
