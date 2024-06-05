<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\SimpleType;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Encoder\SimpleType\SimpleListEncoder;
use Soap\Encoding\Encoder\SimpleType\StringTypeEncoder;
use Soap\Encoding\Test\Unit\Encoder\AbstractEncoderTests;
use Soap\Engine\Metadata\Model\TypeMeta;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(SimpleListEncoder::class)]
final class SimpleListEncoderTest extends AbstractEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => $encoder = new SimpleListEncoder(new StringTypeEncoder()),
            'context' => $context = self::createContext(
                XsdType::guess('string')
                    ->withMeta(
                        static fn (TypeMeta $meta): TypeMeta => $meta
                            ->withIsList(true)
                            ->withIsSimple(true)
                    )
            ),
        ];

        yield 'empty' => [
            ...$baseConfig,
            'xml' => '',
            'data' => [],
        ];
        yield 'single' => [
            ...$baseConfig,
            'xml' => 'hello',
            'data' => ['hello'],
        ];
        yield 'multi' => [
            ...$baseConfig,
            'xml' => 'hello world',
            'data' => ['hello', 'world'],
        ];
    }
}
