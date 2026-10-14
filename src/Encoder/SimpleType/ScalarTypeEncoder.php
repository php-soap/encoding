<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Exception\RestrictionException;
use VeeWee\Reflecta\Iso\Iso;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function ltrim;
use function preg_match;

/**
 * @implements XmlEncoder<mixed, scalar, string, string>
 */
final class ScalarTypeEncoder implements XmlEncoder
{
    public static function default(): self
    {
        /** @psalm-var ScalarTypeEncoder $instance */
        static $instance = new self();

        return $instance;
    }

    /**
     * Will parse scalar values but accepts mixed to throw exceptions on invalid types.
     *
     * @return Iso<mixed, scalar, string, string>
     */
    public function iso(Context $context): Iso
    {
        return (new Iso(
            static fn (mixed $value): string => match(true) {
                is_int($value) => (new IntTypeEncoder())->iso($context)->to($value),
                is_float($value) => (new FloatTypeEncoder())->iso($context)->to($value),
                is_string($value) => (new StringTypeEncoder())->iso($context)->to($value),
                is_bool($value) => (new BoolTypeEncoder())->iso($context)->to($value),
                default => throw RestrictionException::unsupportedValueType($context->type, $value)
            },
            self::decodeUntyped(...),
        ));
    }

    /**
     * Decodes like coercing to int, float, the 'true' or 'false' literal and string in that order, without throwing on each miss.
     * The int and float checks repeat Psl's IntType and FloatType coercion of a string.
     */
    private static function decodeUntyped(string $value): int|float|bool|string
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

        if (preg_match('/^[+-]?(\d+([.]\d*)?([eE][+-]?\d+)?|[.]\d+([eE][+-]?\d+)?)$/', $value) === 1) {
            return (float) $value;
        }

        return match ($value) {
            'true' => true,
            'false' => false,
            default => $value,
        };
    }
}
