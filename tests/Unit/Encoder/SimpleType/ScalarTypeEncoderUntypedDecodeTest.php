<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\SimpleType;

use ErrorException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psl\Type;
use Soap\Encoding\Encoder\SimpleType\ScalarTypeEncoder;
use Soap\Encoding\Test\Unit\ContextCreatorTrait;
use Soap\Engine\Metadata\Model\XsdType;
use function restore_error_handler;
use function set_error_handler;
use function var_export;

/**
 * Untyped values are decoded as the first of int, float, bool and string that accepts them.
 * The int and float rules are Psl's coercion rules, so Psl is the oracle here: a Psl upgrade that changes them turns this test red.
 */
#[CoversClass(ScalarTypeEncoder::class)]
final class ScalarTypeEncoderUntypedDecodeTest extends TestCase
{
    use ContextCreatorTrait;

    private const SWEEP_ALPHABET = ['0', '1', '9', '+', '-', '.', 'e', 'E', ' ', "\n", 'x', 't'];
    private const SWEEP_MAX_LENGTH = 4;

    #[DataProvider('provideNamedCases')]
    public function test_it_decodes_untyped_values(string $value, int|float|bool|string $expected): void
    {
        static::assertSame(var_export($expected, true), var_export(self::oracle($value), true), 'The oracle disagrees with the expected value');
        static::assertSame(var_export($expected, true), var_export(self::decode($value), true));
    }

    public function test_it_decodes_like_psl_for_every_short_string(): void
    {
        $mismatches = [];
        foreach ([...self::sweep(), ...self::boundaries()] as $value) {
            $expected = var_export(self::oracle($value), true);
            $actual = var_export(self::decode($value), true);
            if ($expected !== $actual) {
                $mismatches[] = var_export($value, true) . ': expected ' . $expected . ', got ' . $actual;
            }
        }

        static::assertSame([], $mismatches);
    }

    /**
     * @return iterable<string, array{string, int|float|bool|string}>
     */
    public static function provideNamedCases(): iterable
    {
        yield 'zero' => ['0', 0];
        yield 'one is an int, not a bool' => ['1', 1];
        yield 'int' => ['7', 7];
        yield 'negative int' => ['-12', -12];
        yield 'int max' => ['9223372036854775807', PHP_INT_MAX];
        yield 'leading zeros' => ['007', 7];
        yield 'more leading zeros' => ['0012', 12];
        yield 'only zeros' => ['000', 0];
        yield 'two zeros' => ['00', 0];
        yield 'psl quirk: zeros before a minus are trimmed into a negative int' => ['0-5', -5];
        yield 'psl quirk with two zeros' => ['00-5', -5];
        yield 'negative with leading zero is a float' => ['-012', -12.0];
        yield 'plus sign is a float' => ['+5', 5.0];
        yield 'negative zero is a float' => ['-0', -0.0];
        yield 'above int max is a float' => ['9223372036854775808', 9223372036854775808.0];
        yield 'far above int max is a float' => ['99999999999999999999', 99999999999999999999.0];
        yield 'decimal' => ['1.5', 1.5];
        yield 'decimal without leading digit' => ['.5', 0.5];
        yield 'decimal without trailing digit' => ['5.', 5.0];
        yield 'exponent' => ['1e3', 1000.0];
        yield 'negative upper case exponent' => ['1E-3', 0.001];
        yield 'signed exponent' => ['+1.5e+2', 150.0];
        yield 'overflowing exponent' => ['1e999', INF];
        yield 'decimal with trailing newline' => ["1.5\n", 1.5];
        yield 'int with trailing newline is a float' => ["12\n", 12.0];
        yield 'leading space' => [' 12', ' 12'];
        yield 'trailing space' => ['12 ', '12 '];
        yield 'leading tab' => ["\t1", "\t1"];
        yield 'underscore separator' => ['1_000', '1_000'];
        yield 'hexadecimal' => ['0x1A', '0x1A'];
        yield 'decimal comma' => ['1,5', '1,5'];
        yield 'exponent without digits' => ['1e', '1e'];
        yield 'exponent without mantissa' => ['e1', 'e1'];
        yield 'double minus' => ['--1', '--1'];
        yield 'plus' => ['+', '+'];
        yield 'minus' => ['-', '-'];
        yield 'dot' => ['.', '.'];
        yield 'empty' => ['', ''];
        yield 'true' => ['true', true];
        yield 'false' => ['false', false];
        yield 'upper case true stays a string' => ['TRUE', 'TRUE'];
        yield 'capitalised false stays a string' => ['False', 'False'];
        yield 'yes' => ['yes', 'yes'];
        yield 'on' => ['on', 'on'];
        yield 'null' => ['null', 'null'];
        yield 'nan' => ['NaN', 'NaN'];
        yield 'inf' => ['INF', 'INF'];
        yield 'word' => ['abc', 'abc'];
    }

    private static function decode(string $value): mixed
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            return ScalarTypeEncoder::default()->iso(self::createContext(XsdType::any()))->from($value);
        } finally {
            restore_error_handler();
        }
    }

    private static function oracle(string $value): int|float|bool|string
    {
        try {
            return Type\int()->coerce($value);
        } catch (Type\Exception\CoercionException) {
        }

        try {
            return Type\float()->coerce($value);
        } catch (Type\Exception\CoercionException) {
        }

        return match ($value) {
            'true' => true,
            'false' => false,
            default => $value,
        };
    }

    /**
     * @return list<string>
     */
    private static function sweep(): array
    {
        $values = [''];
        $layer = [''];
        for ($length = 1; $length <= self::SWEEP_MAX_LENGTH; $length++) {
            $next = [];
            foreach ($layer as $prefix) {
                foreach (self::SWEEP_ALPHABET as $char) {
                    $next[] = $prefix . $char;
                }
            }
            $values = [...$values, ...$next];
            $layer = $next;
        }

        return $values;
    }

    /**
     * @return list<string>
     */
    private static function boundaries(): array
    {
        return [
            '9223372036854775807',
            '9223372036854775808',
            '-9223372036854775808',
            '-9223372036854775809',
            '09223372036854775807',
            '+9223372036854775807',
            '9223372036854775807.0',
            '18446744073709551616',
        ];
    }
}
