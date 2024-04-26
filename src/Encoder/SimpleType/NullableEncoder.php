<?php

declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use VeeWee\Reflecta\Iso\Iso;

/**
 * @template T of mixed
 * @implements XmlEncoder<string|null, T>
 */
final class NullableEncoder implements XmlEncoder
{
    /**
     * @param XmlEncoder<string, T> $typeEncoder
     */
    public function __construct(
        private readonly XmlEncoder $typeEncoder
    ) {
    }


    /**
     * @return Iso<string, T>
     */
    public function iso(Context $context): Iso
    {
        $type = $context->type;
        $meta = $type->getMeta();
        $typeEncoder = $this->typeEncoder->iso($context);

        $isNullable = $meta->isNullable()->unwrapOr(false);
        if (!$isNullable) {
            return $typeEncoder;
        }

        return (new Iso(
            /**
             * @param T $value
             */
            static fn (mixed $value): ?string => $value === null ? null : $typeEncoder->to($value),
            /**
             * @return T
             */
            static fn (?string $value): mixed => ($value === null) ? null : $typeEncoder->from($value)
        ));
    }
}
