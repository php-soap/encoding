<?php

declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Xml\Writer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Xml\Writer\OperationBuilder;
use Soap\Engine\Metadata\Model\MethodMeta;
use Soap\WsdlReader\Model\Definitions\BindingStyle;
use Soap\WsdlReader\Model\Definitions\Namespaces;
use VeeWee\Xml\Writer\Writer;
use function VeeWee\Xml\Writer\Mapper\memory_output;

#[CoversClass(OperationBuilder::class)]
final class OperationBuilderTest extends TestCase
{
    /**
     *
     * @dataProvider provideOperationCases
     */
    public function test_it_can_write_a_soap_operation(MethodMeta $meta, array $parts, string $expected): void
    {
        $actual = Writer::inMemory()
            ->write(new OperationBuilder($meta, new Namespaces([], []), $parts))
            ->map(memory_output());

        static::assertXmlStringEqualsXmlString($expected, $actual);
    }

    public static function provideOperationCases()
    {
        $methodMeta = (new MethodMeta())
            ->withTargetNamespace('http://tempuri.org/')
            ->withOperationName('Add');

        yield 'document-single-part' => [
            $methodMeta->withBindingStyle(BindingStyle::DOCUMENT->value),
            [
                '<parameters>
                    <tns:a xmlns:tns="http://tempuri.org/">1</tns:a>
                    <tns:b xmlns:tns="http://tempuri.org/">2</tns:b>
                </parameters>'
            ],
            <<<EOXML
                <tns:Add xmlns:tns="http://tempuri.org/">
                    <tns:a xmlns:tns="http://tempuri.org/">1</tns:a>
                    <tns:b xmlns:tns="http://tempuri.org/">2</tns:b>
                </tns:Add>
            EOXML
        ];
        yield 'rpc-single-part' => [
            $methodMeta->withBindingStyle(BindingStyle::RPC->value),
            [
                '<parameters>
                    <tns:a xmlns:tns="http://tempuri.org/">1</tns:a>
                    <tns:b xmlns:tns="http://tempuri.org/">2</tns:b>
                </parameters>'
            ],
            <<<EOXML
                <tns:Add xmlns:tns="http://tempuri.org/">
                    <parameters>
                        <tns:a xmlns:tns="http://tempuri.org/">1</tns:a>
                        <tns:b xmlns:tns="http://tempuri.org/">2</tns:b>
                    </parameters>
                </tns:Add>
            EOXML
        ];
    }
}
