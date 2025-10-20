<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Restriction\WhitespaceRestriction;
use VeeWee\Reflecta\Iso\Iso;
use function Psl\Encoding\Hex\decode;
use function Psl\Encoding\Hex\encode;

/**
 * @implements XmlEncoder<string, string>
 */
final class HexBinaryTypeEncoder implements XmlEncoder
{
    /**
     * @return Iso<string, string>
     */
    public function iso(Context $context): Iso
    {
        return (new Iso(
            static fn (string $value): string => mb_strtoupper(encode($value)),
            static fn (string $value): string => WhitespaceRestriction::collapse(decode($value)),
        ));
    }
}
