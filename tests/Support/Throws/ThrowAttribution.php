<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Support\Throws;

use PHPUnit\Event\Code\Test;
use PHPUnit\Event\Code\TestMethod;
use ReflectionMethod;
use function array_slice;
use function count;
use function file;
use function implode;
use function preg_match;
use function sprintf;
use function uasort;

/**
 * Attributes the throws of a suite run to their sites, keeping only the intermediate ones:
 * thrown while the stack passes through src/, in a test that does not expect an exception.
 */
final class ThrowAttribution
{
    /** @var array<string, string>|null */
    private ?array $previousIni = null;
    private string $currentTest = '';
    private bool $currentExpectsException = false;
    private int $all = 0;
    private int $outsideSrc = 0;
    private int $inTestsExpectingExceptions = 0;
    /** @var array<string, array{throws: int, tests: array<string, true>}> */
    private array $sites = [];
    /** @var array<string, true> */
    private array $tests = [];
    /** @var array<string, true> */
    private array $unsuccessfulTests = [];

    public function testStarted(Test $test): void
    {
        $this->currentTest = $test->id();
        $this->currentExpectsException = $test instanceof TestMethod && self::expectsException($test);
        $this->previousIni ??= ThrowCounter::enable();
    }

    public function testPrinted(string $output): void
    {
        foreach (XdebugExceptionTraceParser::parse($output) as $event) {
            $this->all++;
            if ($this->currentExpectsException) {
                $this->inTestsExpectingExceptions++;
                continue;
            }
            if ($event->srcCaller() === null) {
                $this->outsideSrc++;
                continue;
            }

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

    public function render(): string
    {
        $sites = $this->sites;
        uasort($sites, static fn (array $a, array $b): int => $b['throws'] <=> $a['throws']);

        $lines = [
            sprintf(
                'Intermediate throws: %d in %d tests',
                $this->all - $this->outsideSrc - $this->inTestsExpectingExceptions,
                count($this->tests),
            ),
            sprintf(
                'Not counted: %d outside src/, %d in tests that expect an exception (%d throws in total)',
                $this->outsideSrc,
                $this->inTestsExpectingExceptions,
                $this->all,
            ),
        ];
        if ($this->unsuccessfulTests !== []) {
            $lines[] = sprintf('Warning: %d tests failed or errored, run composer test to see them', count($this->unsuccessfulTests));
        }
        $lines[] = '';
        $lines[] = sprintf('%7s %6s  %s', 'throws', 'tests', 'site');
        foreach ($sites as $site => $count) {
            $lines[] = sprintf('%7d %6d  %s', $count['throws'], count($count['tests']), $site);
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * PHPUnit does not report expectException() as an event, so the test method body is checked instead.
     * A test that catches one of the exceptions this package reports errors with counts as expecting one too.
     */
    private static function expectsException(TestMethod $test): bool
    {
        $method = new ReflectionMethod($test->className(), $test->methodName());
        $file = $method->getFileName();
        $start = $method->getStartLine();
        $end = $method->getEndLine();
        if ($file === false || $start === false || $end === false) {
            return false;
        }

        $body = implode('', array_slice(file($file) ?: [], $start - 1, $end - $start + 1));

        return preg_match(
            '/\bexpectException\w*\s*\(|\bcatch\s*\(\s*\\\\?(?:[\w\\\\]*\\\\)?(?:EncodingException|RestrictionException|SoapFaultException|Throwable|Exception)(?![\w\\\\])/',
            $body
        ) === 1;
    }
}
