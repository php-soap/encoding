<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use VeeWee\Reflecta\Iso\Iso;

/**
 * @implements XmlEncoder<bool, bool, string, string>
 */
final class BoolTypeEncoder implements XmlEncoder
{
    /**
     * @return Iso<bool, bool, string, string>
     */
    public function iso(Context $context): Iso
    {
        return (new Iso(
            static fn (bool $value): string => $value ? 'true' : 'false',
            static fn (string $value): bool => $value === 'true',
        ));
    }
}
