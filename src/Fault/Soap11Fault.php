<?php
declare(strict_types=1);

namespace Soap\Encoding\Fault;

/**
 * @see https://www.w3.org/TR/2000/NOTE-SOAP-20000508/#_Toc478383507
 *
 *  A mandatory faultcode element information item
 *  A mandatory faultstring element information item
 *  An optional faultactor element information item
 *  An optional detail element information item
 */
final class Soap11Fault implements SoapFault
{
    public function __construct(
        public readonly string $faultCode,
        public readonly string $faultString,
        public readonly ?string $faultActor = null,
        public readonly ?string $detail = null
    ) {
    }

    public function code(): string
    {
        return $this->faultCode;
    }

    public function reason(): string
    {
        return $this->faultString;
    }

    public function detail(): ?string
    {
        return $this->detail;
    }
}
