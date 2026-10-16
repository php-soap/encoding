<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Xml\Writer;

use DOMDocument;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Xml\Writer\InMemoryXmlWriter;
use Throwable;
use ValueError;
use VeeWee\Xml\Exception\RuntimeException;
use XMLWriter;
use function VeeWee\Xml\Writer\Builder\element;
use function VeeWee\Xml\Writer\Builder\value;

#[CoversClass(InMemoryXmlWriter::class)]
final class InMemoryXmlWriterTest extends TestCase
{
    private const LIBXML_ISSUE = "[FATAL] : Couldn't find end of Start Tag broken line 1" . PHP_EOL . ' (73) on line 1,8';

    public function test_it_writes_xml(): void
    {
        $actual = (new InMemoryXmlWriter())(element('a', value('hello')));

        static::assertSame('<a>hello</a>', $actual);
    }

    public function test_it_wraps_an_exception_thrown_by_the_writer(): void
    {
        $original = new LogicException('boom', 5);

        try {
            (new InMemoryXmlWriter())(
                static function () use ($original) {
                    throw $original;
                    yield true;
                }
            );
        } catch (Throwable $exception) {
            static::assertInstanceOf(RuntimeException::class, $exception);
            static::assertSame('boom' . PHP_EOL, $exception->getMessage());
            static::assertSame(5, $exception->getCode());
            static::assertSame($original, $exception->getPrevious());

            return;
        }

        static::fail('Expected the write to fail.');
    }

    public function test_it_fails_when_the_writer_returns_false(): void
    {
        try {
            (new InMemoryXmlWriter())(
                static function () {
                    yield false;
                }
            );
        } catch (Throwable $exception) {
            $message = 'Could not write the provided XML to the stream.';
            static::assertInstanceOf(RuntimeException::class, $exception);
            static::assertSame($message . PHP_EOL, $exception->getMessage());

            $previous = $exception->getPrevious();
            static::assertInstanceOf(RuntimeException::class, $previous);
            static::assertSame($message, $previous->getMessage());
            static::assertNull($previous->getPrevious());

            return;
        }

        static::fail('Expected the write to fail.');
    }

    public function test_it_wraps_an_invalid_element_name(): void
    {
        try {
            (new InMemoryXmlWriter())(element('1st', value('hello')));
        } catch (Throwable $exception) {
            $message = 'XMLWriter::startElement(): Argument #2 must be a valid element name, "1st" given';
            static::assertInstanceOf(RuntimeException::class, $exception);
            static::assertSame($message . PHP_EOL, $exception->getMessage());

            $previous = $exception->getPrevious();
            static::assertInstanceOf(ValueError::class, $previous);
            static::assertSame($message, $previous->getMessage());

            return;
        }

        static::fail('Expected the write to fail.');
    }

    public function test_it_fails_on_libxml_issues_raised_while_writing(): void
    {
        try {
            (new InMemoryXmlWriter())(
                static function () {
                    (new DOMDocument())->loadXML('<broken');
                    yield true;
                }
            );
        } catch (Throwable $exception) {
            static::assertInstanceOf(RuntimeException::class, $exception);
            static::assertSame('XML issues detected: ' . PHP_EOL . self::LIBXML_ISSUE, $exception->getMessage());
            static::assertNull($exception->getPrevious());

            return;
        }

        static::fail('Expected the write to fail.');
    }

    public function test_it_adds_libxml_issues_to_an_exception_thrown_by_the_writer(): void
    {
        $original = new LogicException('boom');

        try {
            (new InMemoryXmlWriter())(
                static function () use ($original) {
                    (new DOMDocument())->loadXML('<broken');
                    throw $original;
                    yield true;
                }
            );
        } catch (Throwable $exception) {
            static::assertInstanceOf(RuntimeException::class, $exception);
            static::assertSame('boom' . PHP_EOL . self::LIBXML_ISSUE, $exception->getMessage());
            static::assertSame($original, $exception->getPrevious());

            return;
        }

        static::fail('Expected the write to fail.');
    }

    public function test_it_wraps_once_per_nested_write(): void
    {
        $original = new LogicException('inner');
        $writer = new InMemoryXmlWriter();

        try {
            $writer(
                static function (XMLWriter $xmlWriter) use ($writer, $original) {
                    yield $xmlWriter->writeRaw($writer(
                        static function () use ($original) {
                            throw $original;
                            yield true;
                        }
                    ));
                }
            );
        } catch (Throwable $exception) {
            static::assertInstanceOf(RuntimeException::class, $exception);
            static::assertSame('inner' . PHP_EOL . PHP_EOL, $exception->getMessage());

            $previous = $exception->getPrevious();
            static::assertInstanceOf(RuntimeException::class, $previous);
            static::assertSame('inner' . PHP_EOL, $previous->getMessage());
            static::assertSame($original, $previous->getPrevious());

            return;
        }

        static::fail('Expected the write to fail.');
    }

    public function test_it_clears_libxml_errors_and_restores_the_setting(): void
    {
        $previousSetting = libxml_use_internal_errors(true);
        try {
            (new DOMDocument())->loadXML('<broken');
            static::assertNotEmpty(libxml_get_errors());

            (new InMemoryXmlWriter())(element('a', value('hello')));

            static::assertSame([], libxml_get_errors());
            static::assertTrue(libxml_use_internal_errors());
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousSetting);
        }
    }

    public function test_it_keeps_libxml_errors_disabled_when_they_were(): void
    {
        $previousSetting = libxml_use_internal_errors(false);
        try {
            (new InMemoryXmlWriter())(element('a', value('hello')));

            static::assertFalse(libxml_use_internal_errors());
        } finally {
            libxml_use_internal_errors($previousSetting);
        }
    }
}
