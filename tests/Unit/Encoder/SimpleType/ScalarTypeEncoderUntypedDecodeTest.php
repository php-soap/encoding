<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\SimpleType;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Coerce\Scalar;
use Soap\Encoding\Encoder\SimpleType\ScalarTypeEncoder;
use Soap\Encoding\Test\Unit\ContextCreatorTrait;
use Soap\Engine\Metadata\Model\XsdType;
use function var_export;

#[CoversClass(ScalarTypeEncoder::class)]
final class ScalarTypeEncoderUntypedDecodeTest extends TestCase
{
    use ContextCreatorTrait;

    #[DataProvider('provideValues')]
    public function test_it_decodes_untyped_values_as_scalar_guess(string $value, int|float|bool|string $expected): void
    {
        $decoded = ScalarTypeEncoder::default()->iso(self::createContext(XsdType::any()))->from($value);

        static::assertSame(var_export($expected, true), var_export($decoded, true));
        static::assertSame(var_export(Scalar::guess($value), true), var_export($decoded, true));
    }

    /**
     * @return iterable<string, array{string, int|float|bool|string}>
     */
    public static function provideValues(): iterable
    {
        yield 'int' => ['7', 7];
        yield 'psl quirk' => ['0-5', -5];
        yield 'float' => ['1.5', 1.5];
        yield 'bool' => ['true', true];
        yield 'string' => ['abc', 'abc'];
    }
}
