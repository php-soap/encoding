<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Support\Throws;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Soap\Encoding\Test\Support\Throws\AssertsNoThrows;
use Soap\Encoding\Test\Support\Throws\ThrowCounter;

#[CoversNothing]
#[Group('no-throws')]
final class ThrowCounterTest extends TestCase
{
    use AssertsNoThrows;

    protected function setUp(): void
    {
        if (!ThrowCounter::isAvailable()) {
            static::markTestSkipped('Counting throws needs Xdebug with xdebug.mode=develop, run composer test:throws.');
        }
    }

    public function test_it_sees_exactly_one_caught_exception(): void
    {
        $line = __LINE__ + 3;
        $events = ThrowCounter::record(static function (): void {
            try {
                throw new RuntimeException("canary\nspanning two lines");
            } catch (RuntimeException) {
            }
        });

        static::assertCount(1, $events);
        static::assertSame(RuntimeException::class, $events[0]->class);
        static::assertSame("canary\nspanning two lines", $events[0]->message);
        static::assertSame(__FILE__, $events[0]->file);
        static::assertSame($line, $events[0]->line);
        static::assertNotEmpty($events[0]->frames);
    }

    public function test_it_passes_when_nothing_is_thrown(): void
    {
        static::assertNoThrows(static fn (): int => 1 + 1);
    }

    public function test_it_fails_with_the_throws_grouped_by_site(): void
    {
        try {
            static::assertNoThrows(static function (): void {
                for ($i = 0; $i < 2; $i++) {
                    try {
                        throw new RuntimeException('expected');
                    } catch (RuntimeException) {
                    }
                }
            });
        } catch (AssertionFailedError $failure) {
            static::assertStringContainsString('got 2', $failure->getMessage());
            static::assertStringContainsString(
                '2x RuntimeException at tests/Unit/Support/Throws/ThrowCounterTest.php:',
                $failure->getMessage()
            );

            return;
        }

        static::fail('assertNoThrows did not fail on thrown exceptions.');
    }
}
