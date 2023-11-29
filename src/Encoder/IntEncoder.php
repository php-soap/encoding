<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use VeeWee\Reflecta\Iso\Iso;
use function Psl\Type\int;
use function Psl\Type\string;

/**
 * @implements XmlEncoder<string, int>
 */
class IntEncoder implements XmlEncoder
{
    /**
     * @return Iso<string, int>
     */
    public function iso(Context $context): Iso
    {
        return (new Iso(
            static fn (int $value): string => string()->coerce($value),
            static fn (string $value): int => int()->coerce($value),
        ))->compose(
            (new StringEncoder())->iso($context)
        );
    }
}
