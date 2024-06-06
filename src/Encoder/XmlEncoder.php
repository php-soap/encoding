<?php

declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use VeeWee\Reflecta\Iso\Iso;

/**
 * @template-covariant TData
 * @template-covariant TXml
 */
interface XmlEncoder
{
    /**
     * @return Iso<TData, TXml>
     */
    public function iso(Context $context): Iso;
}
