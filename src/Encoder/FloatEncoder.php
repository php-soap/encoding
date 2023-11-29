<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use VeeWee\Reflecta\Iso\Iso;
use function Psl\Type\float;

/**
 * @implements XmlEncoder<string, float>
 */
class FloatEncoder implements XmlEncoder
{
    /**
     * @return Iso<string, float>
     */
    public function iso(Context $context): Iso
    {
        return (new Iso(
            static fn (float $value): string => (string)$value,
            static fn (string $value): float => float()->coerce($value),
        ))->compose(
            (new StringEncoder())->iso($context)
        );
    }
}
