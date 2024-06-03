<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Restriction;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Restriction\WhitespaceRestriction;

#[CoversClass(WhitespaceRestriction::class)]
class WhitespaceRestrictionTest extends TestCase
{
    /**
     * @test
     * @dataProvider providePreserveValues
     */
    public function it_can_preserve(string $input, string $expected): void
    {
        self::assertSame($expected, WhitespaceRestriction::preserve($input));
    }

    /**
     * @test
     * @dataProvider provideReplaceValues
     */
    public function it_can_replace(string $input, string $expected): void
    {
        self::assertSame($expected, WhitespaceRestriction::replace($input));
    }

    /**
     * @test
     * @dataProvider provideCollapseValues
     */
    public function it_can_collapse(string $input, string $expected): void
    {
        self::assertSame($expected, WhitespaceRestriction::collapse($input));
    }


    public static function providePreserveValues(): iterable
    {
        yield ['foo', 'foo'];
        yield ['foo bar', 'foo bar'];
        yield ['foo bar baz', 'foo bar baz'];
        yield [' foo  bar ', ' foo  bar '];
        yield [" foo \n bar ", " foo \n bar "];
    }

    public static function provideReplaceValues(): iterable
    {
        yield ['foo', 'foo'];
        yield ['foo bar', 'foo bar'];
        yield ['foo bar baz', 'foo bar baz'];
        yield [' foo  bar ', ' foo  bar '];
        yield [" foo \n bar ", " foo   bar "];
    }

    public static function provideCollapseValues(): iterable
    {
        yield ['foo', 'foo'];
        yield ['foo bar', 'foo bar'];
        yield ['foo bar baz', 'foo bar baz'];
        yield [' foo  bar ', 'foo bar'];
        yield [" foo \n bar ", "foo bar"];
    }
}
