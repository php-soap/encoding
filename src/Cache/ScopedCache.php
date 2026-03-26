<?php
declare(strict_types=1);

namespace Soap\Encoding\Cache;

use Closure;
use WeakMap;

/**
 * GC-safe cache scoped to an object's lifetime.
 * When the scope object is garbage collected, all its cached entries are released.
 *
 * @template TScope of object
 * @template TValue
 *
 * @internal
 */
final class ScopedCache
{
    /** @var WeakMap<TScope, array<string, TValue>> */
    private WeakMap $cache;

    public function __construct()
    {
        /** @var WeakMap<TScope, array<string, TValue>> */
        $this->cache = new WeakMap();
    }

    /**
     * @param TScope $scope
     * @param Closure(): TValue $factory
     * @return TValue
     */
    public function lookup(object $scope, string $key, Closure $factory): mixed
    {
        $scopeCache = $this->cache[$scope] ?? [];
        if (!isset($scopeCache[$key])) {
            $scopeCache[$key] = $factory();
            $this->cache[$scope] = $scopeCache;
        }

        return $scopeCache[$key];
    }
}
