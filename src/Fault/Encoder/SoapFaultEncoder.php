<?php
declare(strict_types=1);

namespace Soap\Encoding\Fault\Encoder;

use Soap\Encoding\Fault\SoapFault;
use VeeWee\Reflecta\Iso\Iso;

/**
 * @template TFault of SoapFault
 */
interface SoapFaultEncoder
{
    /**
     * @return Iso<TFault, non-empty-string>
     */
    public function iso(): Iso;
}
