<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\SimpleType;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Encoder\SimpleType\BackedEnumTypeEncoder;
use Soap\Encoding\Test\Fixture\Model\Color;
use Soap\Encoding\Test\Unit\Encoder\AbstractEncoderTests;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(BackedEnumTypeEncoder::class)]
final class BackedEnumTypeEncoderTest extends AbstractEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => $encoder = new BackedEnumTypeEncoder(Color::class),
            'context' => $context = self::createContext(XsdType::guess('backedEnum')),
        ];

        yield 'simple' => [
            ...$baseConfig,
            'xml' => 'green',
            'data' => Color::Green,
        ];
    }
}
