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
final class DateTypeEncoder implements XmlEncoder
{
    public const DATE_FORMAT = 'Y-m-d';

    /**
     * @return Iso<string, \DateTimeInterface>
     */
    public function iso(Context $context): Iso
    {
        return (new Iso(
            static fn (\DateTimeInterface $value): string => $value->format(self::DATE_FORMAT),
            static fn (string $value): \DateTimeInterface => (new \DateTimeImmutable($value))->setTime(0, 0),
        ));
    }
}
