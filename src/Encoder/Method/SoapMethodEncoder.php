<?php declare(strict_types=1);

namespace Soap\Encoding\Encoder\Method;

use VeeWee\Reflecta\Iso\Iso;

/**
 * @template-covariant TData
 * @template-covariant TXml
 */
interface SoapMethodEncoder
{
    /**
     * @return Iso<TData, TXml>
     */
    public function iso(MethodContext $context): Iso;
}
