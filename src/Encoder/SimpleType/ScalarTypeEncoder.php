<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use VeeWee\Reflecta\Iso\Iso;
use function Psl\Type\scalar;
use function Psl\Type\string;

/**
 * @implements XmlEncoder<string, scalar>
 */
final class ScalarTypeEncoder implements XmlEncoder
{
    public function iso(Context $context): Iso
    {
        return (new Iso(
            static fn (mixed $value): string => string()->coerce($value),
            static fn (string $value): mixed => scalar()->coerce($value),
        ));
    }
}

