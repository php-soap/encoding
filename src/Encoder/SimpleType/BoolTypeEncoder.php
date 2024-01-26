<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use VeeWee\Reflecta\Iso\Iso;

/**
 * @implements XmlEncoder<string, bool>
 */
final class BoolTypeEncoder implements XmlEncoder
{
    public function iso(Context $context): Iso
    {
        return (new Iso(
            static fn (bool $value): string => $value ? 'true' : 'false',
            static fn (string $value): bool => $value === 'true',
        ));
    }
}
