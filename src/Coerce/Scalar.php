<?php
declare(strict_types=1);

namespace Soap\Encoding\Coerce;

use function ltrim;
use function preg_match;

/**
 * @internal Non-throwing equivalents of Psl's int, float and bool coercion for XML text values.
 */
final class Scalar
{
    /**
     * Accepts exactly the strings Psl\Type\int()->coerce() accepts.
     */
    public static function int(string $value): ?int
    {
        $int = (int) $value;
        if ($value === (string) $int) {
            return $int;
        }

        // Psl trims leading zeros before comparing, which also turns '0-5' into -5.
        $trimmed = ltrim($value, '0');
        $int = (int) $trimmed;
        if ($trimmed === (string) $int) {
            return $int;
        }

        if ($trimmed === '' && $value !== '') {
            return 0;
        }

        return null;
    }

    /**
     * Accepts exactly the strings Psl\Type\float()->coerce() accepts.
     */
    public static function float(string $value): ?float
    {
        if (preg_match('/^[+-]?(\d+([.]\d*)?([eE][+-]?\d+)?|[.]\d+([eE][+-]?\d+)?)$/', $value) === 1) {
            return (float) $value;
        }

        return null;
    }

    public static function bool(string $value): ?bool
    {
        return match ($value) {
            'true' => true,
            'false' => false,
            default => null,
        };
    }

    /**
     * Returns the first of int, float and bool that accepts the value, else the value itself.
     */
    public static function guess(string $value): int|float|bool|string
    {
        return self::int($value) ?? self::float($value) ?? self::bool($value) ?? $value;
    }
}
