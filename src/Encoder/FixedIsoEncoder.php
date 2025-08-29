<?php declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use VeeWee\Reflecta\Iso\Iso;

/**
 * @template S
 * @template A
 * @implements XmlEncoder<S, A>
 */
final readonly class FixedIsoEncoder implements XmlEncoder
{
    /**
     * @param Iso<S, A> $iso
     */
    public function __construct(
        private Iso $iso,
    ) {
    }

    /**
     * @return Iso<S, A>
     */
    public function iso(Context $context): Iso
    {
        return $this->iso;
    }
}
