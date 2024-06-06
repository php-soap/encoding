<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use DateTimeImmutable;
use DateTimeInterface;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use VeeWee\Reflecta\Iso\Iso;

/**
 * @implements XmlEncoder<string, \DateTimeInterface>
 */
final class DateTypeEncoder implements XmlEncoder
{
    public const DATE_FORMAT_TIME_ZONED = 'Y-m-dP';
    public const DATE_FORMAT_LOCAL = 'Y-m-d';

    public function __construct(
        private readonly string $dateFormat
    ) {
    }

    public static function default(): self
    {
        return self::local();
    }

    public static function local(): self
    {
        static $instance = new self(self::DATE_FORMAT_LOCAL);

        return $instance;
    }

    public static function timeZoned(): self
    {
        static $instance = new self(self::DATE_FORMAT_TIME_ZONED);

        return $instance;
    }

    /**
     * @return Iso<string, DateTimeInterface>
     */
    public function iso(Context $context): Iso
    {
        return (new Iso(
            fn (DateTimeInterface $value): string => $value->format($this->dateFormat),
            static fn (string $value): DateTimeInterface => (new DateTimeImmutable($value))->setTime(0, 0),
        ));
    }
}
