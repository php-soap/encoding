<?php declare(strict_types=1);

namespace Soap\Encoding\Encoder\Feature;

use Soap\Encoding\Encoder\XmlEncoder;

/**
 * @template-covariant TData
 * @template-covariant TXml
 */
interface DecoratingEncoder
{
    /**
     * @return XmlEncoder<TData, TXml>
     */
    public function decoratedEncoder(): XmlEncoder;
}
