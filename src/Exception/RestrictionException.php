<?php
declare(strict_types=1);

namespace Soap\Encoding\Exception;

use InvalidArgumentException;
use Psl\Str;
use Soap\Encoding\Formatter\QNameFormatter;
use Soap\Engine\Metadata\Model\XsdType;
use function is_scalar;

final class RestrictionException extends InvalidArgumentException implements ExceptionInterface
{
    /**
     * @param scalar $fixedValue
     * @param scalar $value
     */
    public static function invalidFixedValue(mixed $fixedValue, mixed $value): self
    {
        return new self(sprintf('Provided attribute value should be fixed to %s. Got %s', (string) $fixedValue, (string) $value));
    }

    /**
     * @psalm-param mixed $value
     */
    public static function unsupportedValueType(XsdType $type, mixed $value): self
    {
        return new self(
            Str\format(
                'Unsupported value type for type %s: %s',
                get_debug_type($value),
                (new QNameFormatter())($type->getXmlNamespace(), $type->getXmlTypeName()),
            )
        );
    }

    /**
     * @param list<string> $supportedValues
     * @psalm-param mixed $value
     */
    public static function unexpectedEnumType(XsdType $type, array $supportedValues, mixed $value): self
    {
        return new self(
            Str\format(
                'Unexpected enum value for type %s: %s. Supported values are: %s',
                (new QNameFormatter())($type->getXmlNamespace(), $type->getXmlTypeName()),
                is_scalar($value) ? (string)$value : get_debug_type($value),
                Str\join($supportedValues, ', ')
            )
        );
    }
}
