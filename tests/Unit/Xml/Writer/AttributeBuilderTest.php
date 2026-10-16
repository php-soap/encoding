<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Xml\Writer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Xml\Writer\AttributeBuilder;
use Soap\Engine\Metadata\Model\TypeMeta;
use Soap\Engine\Metadata\Model\XsdType;
use VeeWee\Xml\Writer\Writer;
use function VeeWee\Xml\Writer\Builder\element;
use function VeeWee\Xml\Writer\Mapper\memory_output;

#[CoversClass(AttributeBuilder::class)]
final class AttributeBuilderTest extends TestCase
{
    /**
     * @return iterable<string, array{XsdType, string}>
     */
    public static function provideAttributes(): iterable
    {
        yield 'unqualified' => [static::attribute('label', 'urn:t', 't', false), '<p label="v"/>'];
        yield 'qualified' => [static::attribute('label', 'urn:t', 't', true), '<p t:label="v" xmlns:t="urn:t"/>'];
        yield 'qualified without target namespace' => [static::attribute('label', '', '', true), '<p label="v"/>'];
        yield 'xml namespace, without declaring the reserved xml prefix' => [
            static::attribute('lang', 'http://www.w3.org/XML/1998/namespace', 'xml', true),
            '<p xml:lang="v"/>',
        ];
    }

    #[Test]
    #[DataProvider('provideAttributes')]
    public function it_writes_the_attribute_in_its_namespace(XsdType $type, string $expected): void
    {
        $actual = Writer::inMemory()
            ->write(element('p', new AttributeBuilder($type, 'v')))
            ->map(memory_output());

        static::assertSame($expected, $actual);
    }

    private static function attribute(string $name, string $namespace, string $prefix, bool $qualified): XsdType
    {
        return XsdType::guess('string')
            ->withXmlTargetNodeName($name)
            ->withXmlTargetNamespace($namespace)
            ->withXmlTargetNamespaceName($prefix)
            ->withMeta(static fn (TypeMeta $meta): TypeMeta => $meta->withIsAttribute(true)->withIsQualified($qualified));
    }
}
