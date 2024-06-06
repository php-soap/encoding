<?php
declare(strict_types=1);

namespace Soap\Encoding\Exception;

use InvalidArgumentException;
use Soap\Encoding\Formatter\QNameFormatter;
use Soap\Engine\Metadata\Model\XsdType;
use Psl\Str;

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

    public static function unexpectedEnumType(XsdType $type, array $supportedValues, mixed $value): self
    {
        return new self(
            Str\format(
                'Unexpected enum value for type %s: %s. Supported values are: %s',
                (new QNameFormatter())($type->getXmlNamespace(), $type->getXmlTypeName()),
                $value,
                Str\join($supportedValues, ', ')
            )
        );
    }
}
