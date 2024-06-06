<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Psl\Type;
use RuntimeException;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Exception\InvalidArgumentException;
use Soap\Encoding\Exception\RestrictionException;
use VeeWee\Reflecta\Iso\Iso;

/**
 * @implements XmlEncoder<string, scalar>
 */
final class ScalarTypeEncoder implements XmlEncoder
{
    public static function static(): self
    {
        static $instance = new self();

        return $instance;
    }

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
            static function (string $value) use ($context): mixed {
                try {
                    return Type\int()->coerce($value);
                } catch (Type\Exception\CoercionException) {
                }

                try {
                    return Type\float()->coerce($value);
                } catch (Type\Exception\CoercionException) {
                }

                try {
                    return Type\converted(
                        Type\string(),
                        Type\bool(),
                        static fn (string $value): bool => match ($value) {
                            'true' => true,
                            'false' => false,
                            default => throw RestrictionException::unexpectedEnumType(
                                $context->type,
                                ['true', 'false'],
                                $value
                            )
                        }
                    )->coerce($value);
                } catch (Type\Exception\CoercionException) {
                }

                return Type\string()->coerce($value);
            }
        ));
    }
}
