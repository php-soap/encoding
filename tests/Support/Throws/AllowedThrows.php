<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Support\Throws;

use Throwable;
use function min;
use function sprintf;

/**
 * The intermediate throws the suite makes on purpose, listed per test and site in allowed-throws.php.
 * Each entry allows exactly the listed number of throws, so a new throw in the same test still counts.
 *
 * @psalm-type Entry = array{test: string, throw: class-string<Throwable>, via: string, throws: int, reason: string}
 */
final class AllowedThrows
{
    /** @var array<string, Entry> */
    private array $entries = [];
    /** @var array<string, int> */
    private array $seen = [];

    /**
     * @param list<Entry> $entries
     */
    public function __construct(array $entries)
    {
        foreach ($entries as $entry) {
            $this->entries[self::key($entry['test'], $entry['throw'], $entry['via'])] = $entry;
        }
    }

    public static function fromFile(): self
    {
        /** @var list<Entry> $entries */
        $entries = require __DIR__.'/allowed-throws.php';

        return new self($entries);
    }

    public function allows(string $test, ThrowEvent $event): bool
    {
        $key = self::key($test, $event->class, $event->srcSite() ?? '');
        if (!isset($this->entries[$key])) {
            return false;
        }

        $this->seen[$key] = ($this->seen[$key] ?? 0) + 1;

        return $this->seen[$key] <= $this->entries[$key]['throws'];
    }

    public function allowed(): int
    {
        $allowed = 0;
        foreach ($this->seen as $key => $seen) {
            $allowed += min($seen, $this->entries[$key]['throws']);
        }

        return $allowed;
    }

    /**
     * Entries whose test ran but threw less often than listed, so the list can shrink.
     *
     * @param array<string, true> $ranTests
     *
     * @return list<string>
     */
    public function unused(array $ranTests): array
    {
        $unused = [];
        foreach ($this->entries as $key => $entry) {
            $seen = $this->seen[$key] ?? 0;
            if (isset($ranTests[$entry['test']]) && $seen < $entry['throws']) {
                $unused[] = sprintf(
                    '%s: %s via %s threw %d of %d times',
                    $entry['test'],
                    $entry['throw'],
                    $entry['via'],
                    $seen,
                    $entry['throws'],
                );
            }
        }

        return $unused;
    }

    private static function key(string $test, string $throw, string $via): string
    {
        return $test."\0".$throw."\0".$via;
    }
}
