<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use VeeWee\Reflecta\Iso\Iso;

/**
 * @implements XmlEncoder<string, bool>
 */
class BoolEncoder implements XmlEncoder
{
    /**
     * @return Iso<string, bool>
     */
    public function iso(Context $context): Iso
    {
        return (new Iso(
            static fn (bool $value): string => $value ? 'true' : 'false',
            static fn (string $value): bool => $value === 'true',
        ))->compose(
            (new StringEncoder())->iso($context)
        );
    }
}
