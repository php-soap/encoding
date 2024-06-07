<?php
declare(strict_types=1);

namespace Soap\Encoding\Exception;

use Psl\Str;
use RuntimeException;
use Soap\Encoding\Fault\SoapFault;

final class SoapFaultException extends RuntimeException implements ExceptionInterface
{
    public function __construct(
        private readonly SoapFault $fault
    ) {
        parent::__construct(
            Str\format(
                'SOAP Fault: %s (Code: %s)',
                $this->fault->reason(),
                $this->fault->code(),
            ),
        );
    }

    public function fault(): SoapFault
    {
        return $this->fault;
    }
}
