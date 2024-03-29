<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use VeeWee\Reflecta\Iso\Iso;
use function Psl\Type\backed_enum;

/**
 * @template T of \BackedEnum
 *
 * @implements XmlEncoder<string, T>
 */
final class BackedEnumTypeEncoder implements XmlEncoder
{
    /**
     * @param enum-string<T> $enumClass
     */
    public function __construct(
        private readonly string $enumClass
    ) {
    }

    public function iso(Context $context): Iso
    {
        return (new Iso(
            /**
             * @template T $value
             */
            static fn (\BackedEnum $enum): string => $enum->value,
            /**
             * @return T
             */
            fn (string $value): \BackedEnum => backed_enum($this->enumClass)->coerce($value),
        ));
    }
}
