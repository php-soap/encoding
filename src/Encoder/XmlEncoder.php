<?php

declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use VeeWee\Reflecta\Iso\IsoInterface;

/**
 * @template-covariant TDataIn
 * @template-covariant TDataOut
 * @template-covariant TXmlOut
 * @template-covariant TXmlIn
 */
interface XmlEncoder
{
    /**
     * @return IsoInterface<TDataIn, TDataOut, TXmlOut, TXmlIn>
     */
    public function iso(Context $context): IsoInterface;
}
