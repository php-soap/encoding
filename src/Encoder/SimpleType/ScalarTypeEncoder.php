<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Psl\Type;
use RuntimeException;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Exception\InvalidArgumentException;
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

                // TODO ADD SPECIFIC EXCEPTION...
                default => throw new RuntimeException(
                    'Unsupported scalar type: '.gettype($value) . '. ' . print_r($context->type, true)
                )
            },
            static function (string $value): mixed {
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
                            default => throw new InvalidArgumentException('Invalid boolean value: '.$value)
                        }
                    )->coerce($value);
                } catch (Type\Exception\CoercionException) {
                }

                return Type\string()->coerce($value);
            }
        ));
    }
}
