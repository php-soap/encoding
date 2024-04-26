<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\SimpleType;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Encoder\SimpleType\IntTypeEncoder;
use Soap\Encoding\Encoder\SimpleType\NullableEncoder;
use Soap\Encoding\Encoder\SimpleType\StringTypeEncoder;
use Soap\Encoding\Test\Unit\Encoder\AbstractEncoderTests;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(NullableEncoder::class)]
class NullableEncoderTest extends AbstractEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => $encoder = new NullableEncoder(new StringTypeEncoder()),
            'context' => $context = self::createContext(
                XsdType::guess('string')
                    ->withMeta(static fn ($meta) => $meta
                        ->withIsNullable(true)
                    )
            ),
        ];

        yield 'without-value' => [
            ...$baseConfig,
            'xml' => null,
            'data' => null,
        ];
        yield 'with-empty-value' => [
            ...$baseConfig,
            'xml' => '',
            'data' => '',
        ];
        yield 'with-value' => [
            ...$baseConfig,
            'xml' => 'hello',
            'data' => 'hello',
        ];
        yield 'with-non-nullable-value' => [
            ...$baseConfig,
            'context' => $context->withType(
                $context->type->withMeta(static fn ($meta) => $meta
                    ->withIsNullable(false)
                )
            ),
            'xml' => 'hello',
            'data' => 'hello',
        ];
        yield 'with-int-value' => [
            ...$baseConfig,
            'encoder' => new NullableEncoder(new IntTypeEncoder()),
            'xml' => '42',
            'data' => 42,
        ];
    }
}
