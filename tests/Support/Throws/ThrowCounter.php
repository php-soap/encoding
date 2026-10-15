<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Support\Throws;

use RuntimeException;
use function function_exists;
use function in_array;
use function ini_set;
use function ob_get_clean;
use function ob_start;
use function xdebug_info;

/**
 * Records every exception thrown while an operation runs, caught or not, including the ones the engine throws.
 *
 * Xdebug in develop mode hooks zend_throw_exception_hook and, with xdebug.show_exception_trace on,
 * prints a trace for each throw at the moment it is thrown. A rethrow counts as a second throw.
 */
final class ThrowCounter
{
    public static function isAvailable(): bool
    {
        return function_exists('xdebug_info') && in_array('develop', xdebug_info('mode'), true);
    }

    /**
     * Switches the exception traces on, written to the output. Returns the previous ini values for restore().
     *
     * @return array<string, string>
     */
    public static function enable(): array
    {
        if (!self::isAvailable()) {
            throw new RuntimeException('Counting throws needs Xdebug with xdebug.mode=develop.');
        }

        $previous = [];
        foreach ([
            'display_errors' => '1',
            'log_errors' => '0',
            'html_errors' => '0',
            'xdebug.var_display_max_children' => '0',
            'xdebug.var_display_max_data' => '0',
            'xdebug.var_display_max_depth' => '0',
            'xdebug.show_exception_trace' => '1',
        ] as $key => $value) {
            $old = ini_set($key, $value);
            if ($old !== false) {
                $previous[$key] = $old;
            }
        }

        return $previous;
    }

    /**
     * @param array<string, string> $previous
     */
    public static function restore(array $previous): void
    {
        foreach ($previous as $key => $value) {
            ini_set($key, $value);
        }
    }

    /**
     * @return list<ThrowEvent>
     */
    public static function record(callable $operation): array
    {
        $previous = self::enable();
        ob_start();
        try {
            $operation();
        } finally {
            $output = (string) ob_get_clean();
            self::restore($previous);
        }

        return XdebugExceptionTraceParser::parse($output);
    }
}
