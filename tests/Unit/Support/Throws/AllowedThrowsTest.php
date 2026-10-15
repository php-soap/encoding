<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Support\Throws;

use LogicException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Soap\Encoding\Test\Support\Throws\AllowedThrows;
use Soap\Encoding\Test\Support\Throws\ThrowEvent;
use Soap\Encoding\Test\Support\Throws\ThrowFrame;
use Throwable;
use function dirname;

#[CoversNothing]
final class AllowedThrowsTest extends TestCase
{
    private const TEST = 'Some\\Test::test_it_throws_on_purpose';

    public function test_it_allows_a_listed_throw_as_often_as_listed(): void
    {
        $allowed = self::allowed(throws: 2);

        static::assertTrue($allowed->allows(self::TEST, self::event(RuntimeException::class)));
        static::assertTrue($allowed->allows(self::TEST, self::event(RuntimeException::class)));
        static::assertFalse($allowed->allows(self::TEST, self::event(RuntimeException::class)));
        static::assertSame(2, $allowed->allowed());
    }

    public function test_it_only_allows_the_listed_test_class_and_site(): void
    {
        $allowed = self::allowed(throws: 1);

        static::assertFalse($allowed->allows('Some\\Test::test_something_else', self::event(RuntimeException::class)));
        static::assertFalse($allowed->allows(self::TEST, self::event(LogicException::class)));
        static::assertFalse($allowed->allows(self::TEST, self::event(RuntimeException::class, 'Other\\Lens->get')));
        static::assertSame(0, $allowed->allowed());
    }

    public function test_it_reports_entries_of_tests_that_ran_without_throwing_as_listed(): void
    {
        $allowed = self::allowed(throws: 2);
        $allowed->allows(self::TEST, self::event(RuntimeException::class));

        static::assertSame([], $allowed->unused([]));
        static::assertSame(
            [self::TEST.': RuntimeException via src/Encoder/ObjectEncoder.php -> Some\\Lens->get threw 1 of 2 times'],
            $allowed->unused([self::TEST => true]),
        );
    }

    private static function allowed(int $throws): AllowedThrows
    {
        return new AllowedThrows([
            [
                'test' => self::TEST,
                'throw' => RuntimeException::class,
                'via' => 'src/Encoder/ObjectEncoder.php -> Some\\Lens->get',
                'throws' => $throws,
                'reason' => 'The fixture throws on purpose.',
            ],
        ]);
    }

    /**
     * @param class-string<Throwable> $class
     */
    private static function event(string $class, string $srcFunction = 'Some\\Lens->get'): ThrowEvent
    {
        $root = dirname(__DIR__, 4);

        return new ThrowEvent($class, 'message', $root.'/vendor/some/Thrower.php', 12, [
            new ThrowFrame('{main}', $root.'/vendor/bin/phpunit', 0),
            new ThrowFrame($srcFunction, $root.'/src/Encoder/ObjectEncoder.php', 160),
        ]);
    }
}
