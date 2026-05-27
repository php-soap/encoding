<?php declare(strict_types=1);

namespace Soap\Encoding\Encoder\Feature;

use Soap\Encoding\Encoder\XmlEncoder;

/**
 * @template-covariant TDataIn
 * @template-covariant TDataOut
 * @template-covariant TXmlOut
 * @template-covariant TXmlIn
 */
interface DecoratingEncoder
{
    /**
     * @return XmlEncoder<TDataIn, TDataOut, TXmlOut, TXmlIn>
     */
    public function decoratedEncoder(): XmlEncoder;
}
