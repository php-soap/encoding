<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Cache;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Cache\ScopedCache;
use stdClass;

#[CoversClass(ScopedCache::class)]
final class ScopedCacheTest extends TestCase
{
    public function test_it_returns_cached_value_on_hit(): void
    {
        /** @var ScopedCache<stdClass, string> */
        $cache = new ScopedCache();
        $scope = new stdClass();

        $result1 = $cache->lookup($scope, 'key', static fn () => 'built');
        $result2 = $cache->lookup($scope, 'key', static fn () => 'should not be called');

        static::assertSame('built', $result1);
        static::assertSame('built', $result2);
    }

    public function test_it_calls_factory_on_miss(): void
    {
        /** @var ScopedCache<stdClass, string> */
        $cache = new ScopedCache();
        $scope = new stdClass();

        $calls = 0;
        $cache->lookup($scope, 'a', static function () use (&$calls) {
            $calls++;

            return 'value';
        });
        $cache->lookup($scope, 'b', static function () use (&$calls) {
            $calls++;

            return 'other';
        });

        static::assertSame(2, $calls);
    }

    public function test_it_separates_keys_within_same_scope(): void
    {
        /** @var ScopedCache<stdClass, string> */
        $cache = new ScopedCache();
        $scope = new stdClass();

        $a = $cache->lookup($scope, 'a', static fn () => 'alpha');
        $b = $cache->lookup($scope, 'b', static fn () => 'beta');

        static::assertSame('alpha', $a);
        static::assertSame('beta', $b);
    }

    public function test_it_separates_scopes(): void
    {
        /** @var ScopedCache<stdClass, string> */
        $cache = new ScopedCache();
        $scope1 = new stdClass();
        $scope2 = new stdClass();

        $a = $cache->lookup($scope1, 'key', static fn () => 'from scope 1');
        $b = $cache->lookup($scope2, 'key', static fn () => 'from scope 2');

        static::assertSame('from scope 1', $a);
        static::assertSame('from scope 2', $b);
    }

    public function test_it_releases_entries_when_scope_is_garbage_collected(): void
    {
        /** @var ScopedCache<stdClass, string> */
        $cache = new ScopedCache();
        $scope = new stdClass();

        $cache->lookup($scope, 'key', static fn () => str_repeat('x', 1024));

        unset($scope);
        gc_collect_cycles();

        // Create a new scope with a new key; factory must be called (no stale entry)
        $newScope = new stdClass();
        $calls = 0;
        $cache->lookup($newScope, 'key', static function () use (&$calls) {
            $calls++;

            return 'fresh';
        });

        static::assertSame(1, $calls);
    }
}
