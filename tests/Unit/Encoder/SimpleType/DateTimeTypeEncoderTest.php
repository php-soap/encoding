<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\SimpleType;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Encoder\SimpleType\DateTimeTypeEncoder;
use Soap\Encoding\Test\Unit\Encoder\AbstractEncoderTests;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(DateTimeTypeEncoder::class)]
final class DateTimeTypeEncoderTest extends AbstractEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => $encoder = DateTimeTypeEncoder::default(),
            'context' => $context = self::createContext(XsdType::guess('dateTime')),
        ];

        yield 'valid-default-date' => [
            ...$baseConfig,
            'xml' => '2002-05-30T09:00:00+00:00',
            'data' => DateTimeImmutable::createFromFormat(DateTimeTypeEncoder::DATE_FORMAT_TIME_ZONED, '2002-05-30T09:00:00+00:00'),
        ];
        yield 'valid-timezoned-date' => [
            ...$baseConfig,
            'encoder' => DateTimeTypeEncoder::timeZoned(),
            'xml' => '2002-05-30T09:00:00+00:00',
            'data' => DateTimeImmutable::createFromFormat(DateTimeTypeEncoder::DATE_FORMAT_TIME_ZONED, '2002-05-30T09:00:00+00:00'),
        ];
        yield 'valid-local-date' => [
            ...$baseConfig,
            'encoder' => DateTimeTypeEncoder::local(),
            'xml' => '2002-05-30T09:00:00',
            'data' => DateTimeImmutable::createFromFormat(DateTimeTypeEncoder::DATE_FORMAT_LOCAL, '2002-05-30T09:00:00'),
        ];
        yield 'valid-custom-date' => [
            ...$baseConfig,
            'encoder' => new DateTimeTypeEncoder('Y-m-d'),
            'xml' => '2002-05-30',
            'data' => DateTimeImmutable::createFromFormat('!Y-m-d', '2002-05-30'),
        ];
    }
}
