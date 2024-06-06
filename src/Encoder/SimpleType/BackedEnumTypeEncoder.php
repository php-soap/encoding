<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use BackedEnum;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use VeeWee\Reflecta\Iso\Iso;
use function Psl\Type\backed_enum;

/**
 * @implements XmlEncoder<BackedEnum, string>
 */
final class BackedEnumTypeEncoder implements XmlEncoder
{
    /**
     * @param class-string<BackedEnum> $enumClass
     */
    public function __construct(
        private readonly string $enumClass
    ) {
    }

    /**
     * @return Iso<BackedEnum, string>
     */
    public function iso(Context $context): Iso
    {
        return (
            new Iso(
                static fn (BackedEnum $enum): string => (string) $enum->value,
                fn (string $value): BackedEnum => backed_enum($this->enumClass)->coerce($value),
            )
        );
    }
}
