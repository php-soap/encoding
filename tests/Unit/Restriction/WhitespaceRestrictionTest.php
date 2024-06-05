<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Restriction;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Restriction\WhitespaceRestriction;

#[CoversClass(WhitespaceRestriction::class)]
final class WhitespaceRestrictionTest extends TestCase
{
    /**
     *
     * @dataProvider providePreserveValues
     */
    public function test_it_can_preserve(string $input, string $expected): void
    {
        static::assertSame($expected, WhitespaceRestriction::preserve($input));
    }

    /**
     *
     * @dataProvider provideReplaceValues
     */
    public function test_it_can_replace(string $input, string $expected): void
    {
        static::assertSame($expected, WhitespaceRestriction::replace($input));
    }

    /**
     *
     * @dataProvider provideCollapseValues
     */
    public function test_it_can_collapse(string $input, string $expected): void
    {
        static::assertSame($expected, WhitespaceRestriction::collapse($input));
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
