<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use VeeWee\Reflecta\Iso\Iso;
use function Psl\Type\scalar;
use function Psl\Type\string;

/**
 * @implements XmlEncoder<string, scalar>
 */
class ScalarEncoder implements XmlEncoder
{
    /**
     * @return Iso<string, scalar>
     */
    public function iso(Context $context): Iso
    {
        return (new Iso(
            static fn (mixed $value): string => string()->coerce($value),
            static fn (string $value): mixed => scalar()->coerce($value),
        ))->compose(
            (new StringEncoder())->iso($context)
        );
    }
}
