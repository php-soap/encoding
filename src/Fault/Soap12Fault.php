<?php
declare(strict_types=1);

namespace Soap\Encoding\Fault;

/**
 * https://www.w3.org/TR/soap12-part1/#soapfault
 *
 * A mandatory Code element information item (see 5.4.1 SOAP Code Element).
 * A mandatory Reason element information item (see 5.4.2 SOAP Reason Element).
 * An optional Node element information item (see 5.4.3 SOAP Node Element).
 * An optional Role element information item (see 5.4.4 SOAP Role Element).
 * An optional Detail element information item (see 5.4.5 SOAP Detail Element).
 */
final class Soap12Fault implements SoapFault
{
    public function __construct(
        public readonly string $code,
        public readonly string $reason,
        public readonly ?string $subCode = null,
        public readonly ?string $node = null,
        public readonly ?string $role = null,
        public readonly ?string $detail = null
    ) {
    }

    public function code(): string
    {
        return $this->code;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function detail(): ?string
    {
        return $this->detail;
    }
}
