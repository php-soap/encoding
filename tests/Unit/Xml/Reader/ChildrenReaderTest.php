<?php

declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Xml\Writer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Xml\Reader\ChildrenReader;

#[CoversClass(ChildrenReader::class)]
final class ChildrenReaderTest extends TestCase
{
    /**
     *
     * @dataProvider provideChildrenCases
     */
    public function test_it_can_read_children(string $xml, string $expected): void
    {
        $reader = new ChildrenReader();
        $actual = $reader($xml);

        static::assertSame($expected, $actual);
    }

    public static function provideChildrenCases()
    {
        yield 'no-child' => [
            '<Request></Request>',
            '',
        ];
        yield 'single-child' => [
            '<Request><a>a</a></Request>',
            '<a>a</a>',
        ];
        yield 'multi-child' => [
            '<Request><a>a</a><b>b</b></Request>',
            '<a>a</a><b>b</b>',
        ];
    }
}
