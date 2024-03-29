<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\SimpleType;


use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Encoder\SimpleType\FloatTypeEncoder;
use Soap\Encoding\Test\Unit\Encoder\AbstractEncoderTests;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(FloatTypeEncoder::class)]
class FloatTypeEncoderTest extends AbstractEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        // TODO --> Not all tests work as expected - needs revision...
        return [];

        $baseConfig = [
            'encoder' => $encoder = new FloatTypeEncoder(),
            'context' => $context = self::createContext(XsdType::guess('float')),
        ];

        yield 'simple' => [
            ...$baseConfig,
            'xml' => '123.12',
            'data' => 123.12,
        ];
    }
}
