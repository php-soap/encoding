<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use VeeWee\Reflecta\Iso\Iso;
use function Psl\Type\float;
use function Psl\Type\numeric_string;

/**
 * @implements XmlEncoder<float, string>
 */
final class FloatTypeEncoder implements XmlEncoder
{
    /**
     * @return Iso<float, string>
     */
    public function iso(Context $context): Iso
    {
        return (new Iso(
            static fn (float $value): string => numeric_string()->coerce($value),
            static fn (string $value): float => float()->coerce($value),
        ));
    }
}
