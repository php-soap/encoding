<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\SimpleType;


use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Encoder\SimpleType\DateTimeTypeEncoder;
use Soap\Encoding\Test\Unit\Encoder\AbstractEncoderTests;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(DateTimeTypeEncoder::class)]
class DateTimeTypeEncoderTest extends AbstractEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => $encoder = new DateTimeTypeEncoder(),
            'context' => $context = self::createContext(XsdType::guess('dateTime')),
        ];

        yield 'valid-date' => [
            ...$baseConfig,
            'xml' => '2002-05-30T09:00:00',
            'data' => \DateTimeImmutable::createFromFormat(DateTimeTypeEncoder::DATE_FORMAT, '2002-05-30T09:00:00'),
        ];
    }
}
