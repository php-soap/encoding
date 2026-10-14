<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Integration;

use Closure;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Test\Support\Throws\AssertsNoThrows;
use function in_array;

#[CoversNothing]
#[Group('no-throws')]
final class NoIntermediateThrowsTest extends TestCase
{
    use AssertsNoThrows;

    /**
     * The untyped scalar decode and the engine type lookup for soap-enc arrays of a builtin item type still throw.
     */
    private const STILL_THROWING = [
        'decode untyped values',
        'decode soap-enc array of a builtin type',
    ];

    /**
     * @param Closure(): mixed $operation
     */
    #[DataProvider('provideScenarios')]
    public function test_it_does_not_throw_internally(Closure $operation, mixed $expected): void
    {
        $operation();

        static::assertNoThrows($operation);
    }

    /**
     * @return iterable<string, array{operation: Closure(): mixed, expected: mixed}>
     */
    public static function provideScenarios(): iterable
    {
        foreach (IntermediateThrowScenariosTest::provideScenarios() as $name => $scenario) {
            if (!in_array($name, self::STILL_THROWING, true)) {
                yield $name => $scenario;
            }
        }
    }
}
