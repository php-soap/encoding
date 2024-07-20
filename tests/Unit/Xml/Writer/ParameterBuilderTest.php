<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Xml\Writer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\EncoderRegistry;
use Soap\Encoding\Xml\Writer\ParameterBuilder;
use Soap\Engine\Metadata\Collection\MethodCollection;
use Soap\Engine\Metadata\Collection\PropertyCollection;
use Soap\Engine\Metadata\Collection\TypeCollection;
use Soap\Engine\Metadata\InMemoryMetadata;
use Soap\Engine\Metadata\Model\MethodMeta;
use Soap\Engine\Metadata\Model\Property;
use Soap\Engine\Metadata\Model\Type;
use Soap\Engine\Metadata\Model\TypeMeta;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\WsdlReader\Model\Definitions\BindingStyle;
use Soap\WsdlReader\Model\Definitions\Namespaces;
use VeeWee\Xml\Writer\Writer;
use function VeeWee\Xml\Writer\Mapper\memory_output;

#[CoversClass(ParameterBuilder::class)]
final class ParameterBuilderTest extends TestCase
{
    /**
     * @dataProvider provideParameterCases
     */
    public function test_it_can_write_a_soap_operation(MethodMeta $meta, Context $context, mixed $value, string $expected): void
    {
        $actual = Writer::inMemory()
            ->write(new ParameterBuilder($meta, $context, $value))
            ->map(memory_output());

        static::assertXmlStringEqualsXmlString($expected, $actual);
    }

    public static function provideParameterCases()
    {
        $methodMeta = (new MethodMeta())
            ->withTargetNamespace('http://tempuri.org/')
            ->withOperationName('Add');

        $int = XsdType::guess('int')
            ->withMeta(
                static fn (TypeMeta $typeMeta) => $typeMeta
                    ->withIsSimple(true)
                    ->withIsElement(true)
            );

        $context = new Context(
            $type = XsdType::guess('MyRequest')
                ->withXmlNamespace('https://tempuri.org/')
                ->withXmlNamespaceName('tns')
                ->withXmlTargetNodeName('parameters')
                ->withXmlTargetNamespace('https://tempuri.org/')
                ->withXmlTargetNamespaceName('tns')
                ->withMeta(
                    static fn (TypeMeta $typeMeta) => $typeMeta
                        ->withIsElement(true)
                ),
            new InMemoryMetadata(new TypeCollection(
                new Type(
                    $type,
                    new PropertyCollection(
                        new Property('a', $int->withXmlTargetNodeName('a')),
                        new Property('b', $int->withXmlTargetNodeName('b')),
                    )
                )
            ), new MethodCollection()),
            EncoderRegistry::default(),
            new Namespaces(
                ['tns' => 'https://tempuri.org/'],
                ['https://tempuri.org/' => 'tns']
            )
        );

        yield 'document-param' => [
            $methodMeta->withBindingStyle(BindingStyle::DOCUMENT->value),
            $context,
            ['a' => 1, 'b' => 2],
            <<<EOXML
                <tns:MyRequest xmlns:tns="https://tempuri.org/">
                    <a>1</a>
                    <b>2</b>
                </tns:MyRequest>
            EOXML
        ];
        yield 'rpc-param' => [
            $methodMeta->withBindingStyle(BindingStyle::RPC->value),
            $context,
            ['a' => 1, 'b' => 2],
            <<<EOXML
                <parameters>
                    <a>1</a>
                    <b>2</b>
                </parameters>
            EOXML
        ];
    }
}
