<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Soap\Encoding\Coerce\Scalar;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Exception\RestrictionException;
use VeeWee\Reflecta\Iso\Iso;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

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
            Scalar::guess(...),
        ));
    }
}
