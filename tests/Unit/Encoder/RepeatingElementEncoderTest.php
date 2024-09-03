<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Encoder\ElementEncoder;
use Soap\Encoding\Encoder\RepeatingElementEncoder;
use Soap\Encoding\Encoder\SimpleType\StringTypeEncoder;
use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Node\ElementList;
use Soap\Engine\Metadata\Model\TypeMeta;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\Xml\Xmlns;

#[CoversClass(RepeatingElementEncoder::class)]
final class RepeatingElementEncoderTest extends AbstractEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => $encoder = new RepeatingElementEncoder(new ElementEncoder(new StringTypeEncoder())),
            'context' => $context = self::createContext(
                $xsdType = XsdType::guess('string')
                    ->withXmlNamespace(Xmlns::xsd()->value())
                    ->withXmlTargetNodeName('item')
                    ->withMeta(static fn (TypeMeta $meta): TypeMeta => $meta->withIsQualified(true))
            ),
        ];

        yield 'simple-list' => [
            ...$baseConfig,
            'xml' => '<item>a</item><item>b</item>',
            'data' => ['a', 'b'],
        ];
        yield 'namespaced' => [
            ...$baseConfig,
            'context' => $context->withType(
                $namespacedType = $xsdType->withXmlTargetNamespace('http://example.com')
            ),
            'xml' => '<item xmlns="http://example.com">a</item><item xmlns="http://example.com">b</item>',
            'data' => ['a', 'b'],
        ];
        yield 'namespaced-with-prefix' => [
            ...$baseConfig,
            'context' => $context->withType(
                $namespacedType->withXmlTargetNamespaceName('x')
            ),
            'xml' => '<x:item xmlns:x="http://example.com">a</x:item><x:item xmlns:x="http://example.com">b</x:item>',
            'data' => ['a', 'b'],
        ];
    }

    public function test_it_can_decode_from_xml_item(): void
    {
        $encoder = new RepeatingElementEncoder(new ElementEncoder(new StringTypeEncoder()));
        $context = self::createContext(
            $xsdType = XsdType::guess('string')
                ->withXmlTargetNodeName('hello')
                ->withMeta(static fn (TypeMeta $meta): TypeMeta => $meta->withIsQualified(true))
        );

        $item = Element::fromString('<hello>world</hello>');
        $iso = $encoder->iso($context);
        $actual = $iso->from($item);

        static::assertEquals(['world'], $actual);
    }

    public function test_it_can_decode_from_xml_item_list(): void
    {
        $encoder = new RepeatingElementEncoder(new ElementEncoder(new StringTypeEncoder()));
        $context = self::createContext(
            $xsdType = XsdType::guess('string')
                ->withXmlTargetNodeName('hello')
                ->withMeta(static fn (TypeMeta $meta): TypeMeta => $meta->withIsQualified(true))
        );

        $item = new ElementList(Element::fromString('<hello>world</hello>'));
        $iso = $encoder->iso($context);
        $actual = $iso->from($item);

        static::assertEquals(['world'], $actual);
    }

    public function test_it_can_encode_from_null(): void
    {
        $encoder = new RepeatingElementEncoder(new ElementEncoder(new StringTypeEncoder()));
        $context = self::createContext(
            XsdType::guess('string')
                ->withXmlTargetNodeName('hello')
                ->withMeta(static fn (TypeMeta $meta): TypeMeta => $meta->withIsQualified(true))
        );

        $iso = $encoder->iso($context);
        $actual = $iso->to(null);

        static::assertSame('', $actual);
    }
}
