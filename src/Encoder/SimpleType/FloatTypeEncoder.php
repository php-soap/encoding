<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use VeeWee\Reflecta\Iso\Iso;
use function Psl\Type\float;
use function Psl\Type\numeric_string;

/**
 * @implements XmlEncoder<string, float>
 */
final class FloatTypeEncoder implements XmlEncoder
{
    public function iso(Context $context): Iso
    {
        return (new Iso(
            static fn (float $value): string => numeric_string()->coerce($value),
            static fn (string $value): float => float()->coerce($value),
        ));
    }
}
