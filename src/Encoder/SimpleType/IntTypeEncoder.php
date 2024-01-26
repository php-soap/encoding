<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use VeeWee\Reflecta\Iso\Iso;
use function Psl\Type\int;
use function Psl\Type\string;

/**
 * @implements XmlEncoder<string, int>
 */
final class IntTypeEncoder implements XmlEncoder
{
    public function iso(Context $context): Iso
    {
        return (new Iso(
            static fn (int $value): string => string()->coerce($value),
            static fn (string $value): int => int()->coerce($value),
        ));
    }
}
