<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Support\Throws;

use function arsort;
use function count;
use function implode;
use function sprintf;

trait AssertsNoThrows
{
    protected static function assertNoThrows(callable $operation): void
    {
        if (!ThrowCounter::isAvailable()) {
            static::markTestSkipped('Counting throws needs Xdebug with xdebug.mode=develop, run composer test:throws.');
        }

        $events = ThrowCounter::record($operation);

        $grouped = [];
        foreach ($events as $event) {
            $key = $event->describe();
            $grouped[$key] = ($grouped[$key] ?? 0) + 1;
        }
        arsort($grouped);

        $lines = [];
        foreach ($grouped as $description => $count) {
            $lines[] = sprintf('%5dx %s', $count, $description);
        }

        static::assertCount(
            0,
            $events,
            sprintf("Expected no exceptions to be thrown, got %d:\n%s", count($events), implode("\n", $lines)),
        );
    }
}
