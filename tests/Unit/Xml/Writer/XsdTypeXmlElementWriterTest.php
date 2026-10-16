<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Xml\Writer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Test\Unit\ContextCreatorTrait;
use Soap\Encoding\Xml\Writer\XsdTypeXmlElementWriter;
use Soap\Engine\Metadata\Model\XsdType;
use Throwable;
use ValueError;
use VeeWee\Xml\Exception\RuntimeException;
use function VeeWee\Xml\Writer\Builder\value;

#[CoversClass(XsdTypeXmlElementWriter::class)]
final class XsdTypeXmlElementWriterTest extends TestCase
{
    use ContextCreatorTrait;

    public function test_it_writes_an_element(): void
    {
        $actual = (new XsdTypeXmlElementWriter())(self::contextFor('a'), value('hello'));

        static::assertSame('<a>hello</a>', $actual);
    }

    public function test_it_wraps_an_invalid_element_name(): void
    {
        try {
            (new XsdTypeXmlElementWriter())(self::contextFor('1st'), value('hello'));
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

    private static function contextFor(string $nodeName): Context
    {
        return self::createContext(XsdType::guess('string')->withXmlTargetNodeName($nodeName));
    }
}
