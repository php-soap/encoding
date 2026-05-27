<?php declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use VeeWee\Reflecta\Iso\IsoInterface;

/**
 * @template S
 * @template T
 * @template A
 * @template B
 * @implements XmlEncoder<S, T, A, B>
 */
final readonly class FixedIsoEncoder implements XmlEncoder
{
    /**
     * @param IsoInterface<S, T, A, B> $iso
     */
    public function __construct(
        private IsoInterface $iso,
    ) {
    }

    /**
     * @return IsoInterface<S, T, A, B>
     */
    public function iso(Context $context): IsoInterface
    {
        return $this->iso;
    }
}
