<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Exception\InvalidArgumentException;
use VeeWee\Reflecta\Iso\Iso;

/**
 * @implements XmlEncoder<string, \DateTimeInterface>
 */
final class DateTimeTypeEncoder implements XmlEncoder
{
    public const DATE_FORMAT = 'Y-m-d\TH:i:sP';

    /**
     * @return Iso<string, \DateTimeInterface>
     */
    public function iso(Context $context): Iso
    {
        // TODO : Timezones
        // https://www.w3schools.com/xml/schema_dtypes_date.asp

        return (new Iso(
            static fn (\DateTimeInterface $value): string => $value->format(self::DATE_FORMAT),
            static function (string $value): \DateTimeInterface {
                $result = \DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $value);
                if (!$result) {
                    throw new InvalidArgumentException(
                        'Invalid date format detected: '.$value.'. Expected format: '.self::DATE_FORMAT.'.'
                    );
                }

                return $result;
            }
        ));
    }
}
