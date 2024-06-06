<?php
declare(strict_types=1);

namespace Soap\Encoding\Fault;

interface SoapFault
{
    public function code(): string;

    public function reason(): string;

    public function detail(): ?string;
}
