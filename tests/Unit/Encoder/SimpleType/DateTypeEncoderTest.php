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
            'encoder' => $encoder = new DateTypeEncoder(),
            'context' => $context = self::createContext(XsdType::guess('date')),
        ];

        yield 'valid-date' => [
            ...$baseConfig,
            'xml' => '2024-04-26',
            'data' => DateTimeImmutable::createFromFormat('!'.DateTypeEncoder::DATE_FORMAT, '2024-04-26'),
        ];
    }
}
