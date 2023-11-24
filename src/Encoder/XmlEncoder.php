<?php

declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use VeeWee\Reflecta\Iso\Iso;

/**
 * @template I
 * @template O
 */
interface XmlEncoder
{
    /**
     * @return Iso<I, O>
     */
    public function iso(): Iso;
}
