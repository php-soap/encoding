<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Support\Throws;

use PHPUnit\Event\Code\Test;
use PHPUnit\Event\Code\TestMethod;
use ReflectionMethod;
use function array_keys;
use function array_slice;
use function count;
use function file;
use function implode;
use function preg_match;
use function sprintf;
use function uasort;

/**
 * Attributes the throws of a suite run to their sites, keeping only the intermediate ones:
 * thrown while the stack passes through src/, in a test that does not expect an exception
 * and not listed as deliberate in allowed-throws.php.
 */
final class ThrowAttribution
{
    private const SHOWN_TESTS_PER_SITE = 3;

    /** @var array<string, string>|null */
    private ?array $previousIni = null;
    private string $currentTest = '';
    private ?string $currentExclusion = null;
    private int $all = 0;
    private int $outsideSrc = 0;
    private int $intermediate = 0;
    /** @var array{expectException: int, catch: int} */
    private array $excluded = ['expectException' => 0, 'catch' => 0];
    /** @var array<string, list<string>> */
    private array $fileLines = [];
    /** @var array<string, array{throws: int, tests: array<string, true>}> */
    private array $sites = [];
    /** @var array<string, true> */
    private array $tests = [];
    /** @var array<string, true> */
    private array $unsuccessfulTests = [];
    /** @var array<string, true> */
    private array $ranTests = [];

    public function __construct(
        private readonly AllowedThrows $allowedThrows,
    ) {
    }

    public function testStarted(Test $test): void
    {
        $this->currentTest = $test->id();
        $this->ranTests[$this->currentTest] = true;
        $this->currentExclusion = $test instanceof TestMethod ? $this->exclusion($test) : null;
        $this->previousIni ??= ThrowCounter::enable();
    }

    public function testPrinted(string $output): void
    {
        foreach (XdebugExceptionTraceParser::parse($output) as $event) {
            $this->all++;
            if ($this->currentExclusion !== null) {
                $this->excluded[$this->currentExclusion]++;
                continue;
            }
            if ($event->srcCaller() === null) {
                $this->outsideSrc++;
                continue;
            }
            if ($this->allowedThrows->allows($this->currentTest, $event)) {
                continue;
            }

            $this->intermediate++;
            $site = $event->describe();
            $this->sites[$site] ??= ['throws' => 0, 'tests' => []];
            $this->sites[$site]['throws']++;
            $this->sites[$site]['tests'][$this->currentTest] = true;
            $this->tests[$this->currentTest] = true;
        }
    }

    public function testUnsuccessful(Test $test): void
    {
        $this->unsuccessfulTests[$test->id()] = true;
        $this->stopTracing();
    }

    /**
     * Called as soon as the outcome of a test is known: PHPUnit can throw again while it handles a skipped or
     * failed test, after the output buffer of the test is closed, which would print the trace to the console.
     */
    public function stopTracing(): void
    {
        if ($this->previousIni !== null) {
            ThrowCounter::restore($this->previousIni);
            $this->previousIni = null;
        }
    }

    /**
     * True when the run has intermediate throws, allowlist entries that no longer throw as listed,
     * or failed tests, whose throws cannot be trusted.
     */
    public function failed(): bool
    {
        return $this->intermediate > 0
            || $this->allowedThrows->unused($this->ranTests) !== []
            || $this->unsuccessfulTests !== [];
    }

    public function render(): string
    {
        $sites = $this->sites;
        uasort($sites, static fn (array $a, array $b): int => $b['throws'] <=> $a['throws']);

        $lines = [
            sprintf('Intermediate throws: %d in %d tests', $this->intermediate, count($this->tests)),
            sprintf(
                'Not counted: %d outside src/, %d in tests using expectException(), %d in tests catching an exception themselves, %d allowed in tests/Support/Throws/allowed-throws.php (%d throws in total)',
                $this->outsideSrc,
                $this->excluded['expectException'],
                $this->excluded['catch'],
                $this->allowedThrows->allowed(),
                $this->all,
            ),
        ];
        if ($this->unsuccessfulTests !== []) {
            $lines[] = sprintf('Warning: %d tests failed or errored, run composer test to see them', count($this->unsuccessfulTests));
        }
        foreach ($this->allowedThrows->unused($this->ranTests) as $unused) {
            $lines[] = 'Allowed throw no longer thrown as listed, update allowed-throws.php: '.$unused;
        }
        $lines[] = '';
        $lines[] = sprintf('%7s %6s  %s', 'throws', 'tests', 'site');
        foreach ($sites as $site => $count) {
            $lines[] = sprintf('%7d %6d  %s', $count['throws'], count($count['tests']), $site);
            $tests = array_keys($count['tests']);
            foreach (array_slice($tests, 0, self::SHOWN_TESTS_PER_SITE) as $test) {
                $lines[] = sprintf('%15s  %s', '', $test);
            }
            if (count($tests) > self::SHOWN_TESTS_PER_SITE) {
                $lines[] = sprintf('%15s  and %d more', '', count($tests) - self::SHOWN_TESTS_PER_SITE);
            }
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * PHPUnit does not report expectException() as an event, so the test method body is checked instead.
     * A test that catches one of the exceptions this package reports errors with is left out as well,
     * and counted separately in the report so the effect of that heuristic stays visible.
     *
     * @return 'expectException'|'catch'|null
     */
    private function exclusion(TestMethod $test): ?string
    {
        $method = new ReflectionMethod($test->className(), $test->methodName());
        $file = $method->getFileName();
        $start = $method->getStartLine();
        $end = $method->getEndLine();
        if ($file === false || $start === false || $end === false) {
            return null;
        }

        $this->fileLines[$file] ??= file($file) ?: [];
        $body = implode('', array_slice($this->fileLines[$file], $start - 1, $end - $start + 1));

        return match (true) {
            preg_match('/\bexpectException\w*\s*\(/', $body) === 1 => 'expectException',
            preg_match(
                '/\bcatch\s*\(\s*\\\\?(?:[\w\\\\]*\\\\)?(?:EncodingException|RestrictionException|SoapFaultException|Throwable|Exception)(?![\w\\\\])/',
                $body,
            ) === 1 => 'catch',
            default => null,
        };
    }
}
