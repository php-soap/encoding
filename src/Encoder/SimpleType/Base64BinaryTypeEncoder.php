<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Restriction\WhitespaceRestriction;
use VeeWee\Reflecta\Iso\Iso;

/**
 * @implements XmlEncoder<string, string>
 */
final class Base64BinaryTypeEncoder implements XmlEncoder
{
    public function iso(Context $context): Iso
    {
        return (new Iso(
            static fn (string $value): string => base64_encode($value),
            static fn (string $value): string => WhitespaceRestriction::collapse(base64_decode($value, true)),
        ));
    }
}
