<?php
declare(strict_types=1);

namespace Soap\Encoding\Exception;

use InvalidArgumentException;

final class RestrictionException extends InvalidArgumentException implements ExceptionInterface
{
    /**
     * @param scalar $fixedValue
     * @param scalar $value
     */
    public static function invalidFixedValue(mixed $fixedValue, mixed $value): self
    {
        return new self(sprintf('Provided attribute value should be fixed to %s. Got %s', $fixedValue, $value));
    }
}
