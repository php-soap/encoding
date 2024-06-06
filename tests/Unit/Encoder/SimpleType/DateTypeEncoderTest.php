<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\SimpleType;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Encoder\SimpleType\DateTypeEncoder;
use Soap\Encoding\Test\Unit\Encoder\AbstractEncoderTests;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(DateTypeEncoder::class)]
final class DateTypeEncoderTest extends AbstractEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => $encoder = DateTypeEncoder::default(),
            'context' => $context = self::createContext(XsdType::guess('date')),
        ];

        yield 'valid-default' => [
            ...$baseConfig,
            'xml' => '2024-04-26',
            'data' => DateTimeImmutable::createFromFormat('!'.DateTypeEncoder::DATE_FORMAT_LOCAL, '2024-04-26'),
        ];

        yield 'valid-local-date' => [
            ...$baseConfig,
            'encoder' => DateTypeEncoder::local(),
            'xml' => '2024-04-26',
            'data' => DateTimeImmutable::createFromFormat('!'.DateTypeEncoder::DATE_FORMAT_LOCAL, '2024-04-26'),
        ];

        yield 'valid-timezoned-date' => [
            ...$baseConfig,
            'encoder' => DateTypeEncoder::timeZoned(),
            'xml' => '2024-04-26+00:00',
            'data' => DateTimeImmutable::createFromFormat('!'.DateTypeEncoder::DATE_FORMAT_TIME_ZONED, '2024-04-26+00:00'),
        ];

        yield 'valid-custom' => [
            ...$baseConfig,
            'encoder' => new DateTypeEncoder('Y-m-d'),
            'xml' => '2024-04-26',
            'data' => DateTimeImmutable::createFromFormat('!Y-m-d', '2024-04-26'),
        ];
    }
}
