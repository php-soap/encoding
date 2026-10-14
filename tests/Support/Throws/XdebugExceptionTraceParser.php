<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Support\Throws;

use Throwable;
use function explode;
use function is_a;
use function preg_match;
use function trim;

/**
 * Parses the exception traces Xdebug prints while xdebug.show_exception_trace is on (html_errors off):
 *
 *     RuntimeException: message, possibly spanning lines in /path/File.php on line 12
 *
 *     Call Stack:
 *         0.0005     430720   1. {main}() /path/bin/phpunit:0
 *
 * Output that is not part of a trace is ignored.
 */
final class XdebugExceptionTraceParser
{
    /**
     * @return list<ThrowEvent>
     */
    public static function parse(string $output): array
    {
        $events = [];
        $header = null;
        $event = null;

        foreach (explode("\n", $output) as $line) {
            if ($event !== null) {
                if (preg_match('/^\s+[\d.]+\s+\d+\s+\d+\. (.+?)\(.*\) (.+):(\d+)$/', $line, $frame)) {
                    $event['frames'][] = new ThrowFrame($frame[1], $frame[2], (int) $frame[3]);
                    continue;
                }

                $events[] = new ThrowEvent($event['class'], $event['message'], $event['file'], $event['line'], $event['frames']);
                $event = null;
            }

            if ($header !== null) {
                if ($line === 'Call Stack:') {
                    $event = self::parseHeader($header);
                    $header = null;
                } else {
                    $header .= "\n".$line;
                }
                continue;
            }

            if (preg_match('/^([A-Za-z_\\\\][\w\\\\]*): /', $line, $match) && is_a($match[1], Throwable::class, true)) {
                $header = $line;
            }
        }

        if ($event !== null) {
            $events[] = new ThrowEvent($event['class'], $event['message'], $event['file'], $event['line'], $event['frames']);
        }

        return $events;
    }

    /**
     * @return array{class: class-string<Throwable>, message: string, file: string, line: int, frames: list<ThrowFrame>}|null
     */
    private static function parseHeader(string $header): ?array
    {
        if (!preg_match('/^([A-Za-z_\\\\][\w\\\\]*): (.*) in (.+) on line (\d+)\s*$/s', $header, $match)
            || !is_a($match[1], Throwable::class, true)
        ) {
            return null;
        }

        return [
            'class' => $match[1],
            'message' => trim($match[2]),
            'file' => $match[3],
            'line' => (int) $match[4],
            'frames' => [],
        ];
    }
}
