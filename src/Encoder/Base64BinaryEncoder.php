<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use VeeWee\Reflecta\Iso\Iso;

/**
 * @implements XmlEncoder<string, string>
 */
class Base64BinaryEncoder implements XmlEncoder
{
    /**
     * @return Iso<string, string>
     */
    public function iso(): Iso
    {
        return (new Iso(
            base64_encode(...),
            base64_decode(...),
        ))->compose(
            (new StringEncoder())->iso()
        );
    }
}
