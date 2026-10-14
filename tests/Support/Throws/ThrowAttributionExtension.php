<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Support\Throws;

use PHPUnit\Event\Application\Finished as ApplicationFinished;
use PHPUnit\Event\Application\FinishedSubscriber as ApplicationFinishedSubscriber;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\ErroredSubscriber;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\FailedSubscriber;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use PHPUnit\Event\Test\PrintedUnexpectedOutput;
use PHPUnit\Event\Test\PrintedUnexpectedOutputSubscriber;
use PHPUnit\Event\Test\Skipped;
use PHPUnit\Event\Test\SkippedSubscriber;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use RuntimeException;

/**
 * Prints the intermediate throws of a suite run per site and fails the run when there are any, see composer throws:report.
 *
 * Xdebug prints each exception trace into the output buffer of the running test, so every test that throws
 * counts as printing output. The composer script runs with --no-results and --do-not-fail-on-risky for that
 * reason, and this report mentions failed tests instead.
 */
final class ThrowAttributionExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        if (!ThrowCounter::isAvailable()) {
            throw new RuntimeException('The throws report needs Xdebug with xdebug.mode=develop, run composer throws:report.');
        }

        $attribution = new ThrowAttribution(AllowedThrows::fromFile());

        $facade->registerSubscribers(
            new class($attribution) implements PreparationStartedSubscriber {
                public function __construct(private readonly ThrowAttribution $attribution)
                {
                }

                public function notify(PreparationStarted $event): void
                {
                    $this->attribution->testStarted($event->test());
                }
            },
            new class($attribution) implements PrintedUnexpectedOutputSubscriber {
                public function __construct(private readonly ThrowAttribution $attribution)
                {
                }

                public function notify(PrintedUnexpectedOutput $event): void
                {
                    $this->attribution->testPrinted($event->output());
                }
            },
            new class($attribution) implements FailedSubscriber {
                public function __construct(private readonly ThrowAttribution $attribution)
                {
                }

                public function notify(Failed $event): void
                {
                    $this->attribution->testUnsuccessful($event->test());
                }
            },
            new class($attribution) implements ErroredSubscriber {
                public function __construct(private readonly ThrowAttribution $attribution)
                {
                }

                public function notify(Errored $event): void
                {
                    $this->attribution->testUnsuccessful($event->test());
                }
            },
            new class($attribution) implements SkippedSubscriber {
                public function __construct(private readonly ThrowAttribution $attribution)
                {
                }

                public function notify(Skipped $event): void
                {
                    $this->attribution->stopTracing();
                }
            },
            new class($attribution) implements FinishedSubscriber {
                public function __construct(private readonly ThrowAttribution $attribution)
                {
                }

                public function notify(Finished $event): void
                {
                    $this->attribution->stopTracing();
                }
            },
            new class($attribution) implements ExecutionFinishedSubscriber {
                public function __construct(private readonly ThrowAttribution $attribution)
                {
                }

                public function notify(ExecutionFinished $event): void
                {
                    $this->attribution->stopTracing();
                    print "\n".$this->attribution->render();
                }
            },
            new class($attribution) implements ApplicationFinishedSubscriber {
                public function __construct(private readonly ThrowAttribution $attribution)
                {
                }

                public function notify(ApplicationFinished $event): void
                {
                    if ($event->shellExitCode() === 0 && $this->attribution->failed()) {
                        exit(1);
                    }
                }
            },
        );
    }
}
