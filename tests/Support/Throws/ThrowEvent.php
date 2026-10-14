<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Support\Throws;

use Throwable;
use function array_reverse;
use function dirname;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;

final class ThrowEvent
{
    /**
     * @param class-string<Throwable> $class
     * @param list<ThrowFrame> $frames Outermost call first, as Xdebug prints them.
     */
    public function __construct(
        public readonly string $class,
        public readonly string $message,
        public readonly string $file,
        public readonly int $line,
        public readonly array $frames,
    ) {
    }

    public function site(): string
    {
        return self::relative($this->file).':'.$this->line;
    }

    /**
     * The innermost place in src/ that led to this throw, or null when the stack never passes through src/.
     */
    public function srcCaller(): ?string
    {
        if (self::isSrc($this->file)) {
            return $this->site();
        }

        $frame = $this->srcFrame();

        return $frame !== null ? self::relative($frame->file).':'.$frame->line.' -> '.$frame->function : null;
    }

    /**
     * The innermost file and line in src/ that led to this throw, without the function it called.
     */
    public function srcSite(): ?string
    {
        if (self::isSrc($this->file)) {
            return $this->site();
        }

        $frame = $this->srcFrame();

        return $frame !== null ? self::relative($frame->file).':'.$frame->line : null;
    }

    private function srcFrame(): ?ThrowFrame
    {
        foreach (array_reverse($this->frames) as $frame) {
            if (self::isSrc($frame->file)) {
                return $frame;
            }
        }

        return null;
    }

    public function describe(): string
    {
        $caller = $this->srcCaller();

        return sprintf('%s at %s%s', $this->class, $this->site(), $caller !== null ? ' via '.$caller : '');
    }

    private static function isSrc(string $file): bool
    {
        return str_starts_with($file, self::root().'/src/');
    }

    private static function relative(string $file): string
    {
        $root = self::root().'/';

        return str_starts_with($file, $root) ? substr($file, strlen($root)) : $file;
    }

    private static function root(): string
    {
        return dirname(__DIR__, 3);
    }
}
