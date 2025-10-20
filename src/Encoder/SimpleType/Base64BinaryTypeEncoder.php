<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Restriction\WhitespaceRestriction;
use VeeWee\Reflecta\Iso\Iso;
use function Psl\Encoding\Base64\decode;
use function Psl\Encoding\Base64\encode;

/**
 * @implements XmlEncoder<string, string>
 */
final class Base64BinaryTypeEncoder implements XmlEncoder
{
    /**
     * @return Iso<string, string>
     */
    public function iso(Context $context): Iso
    {
        return (new Iso(
            static fn (string $value): string => encode($value),
            static fn (string $value): string => WhitespaceRestriction::collapse(decode($value)),
        ));
    }
}
