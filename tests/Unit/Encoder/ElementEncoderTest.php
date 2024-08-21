<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\ElementEncoder;
use Soap\Encoding\Encoder\Feature\ElementContextEnhancer;
use Soap\Encoding\Encoder\Feature\XsiTypeCalculator;
use Soap\Encoding\Encoder\SimpleType\IntTypeEncoder;
use Soap\Encoding\Encoder\SimpleType\StringTypeEncoder;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Xml\Node\Element;
use Soap\Engine\Metadata\Model\TypeMeta;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use VeeWee\Reflecta\Iso\Iso;

#[CoversClass(ElementEncoder::class)]
final class ElementEncoderTest extends AbstractEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => $encoder = new ElementEncoder(new StringTypeEncoder()),
            'context' => $context = self::createContext(
                $xsdType = XsdType::guess('string')
                    ->withXmlTargetNodeName('hello')
                    ->withMeta(
                        static fn (TypeMeta $meta): TypeMeta => $meta
                        ->withIsQualified(true)
                        ->withIsSimple(true)
                    )
            ),
        ];

        yield 'no-namespace' => [
            ...$baseConfig,
            'xml' => '<hello>world</hello>',
            'data' => 'world',
        ];
        yield 'no-namespace-special-chars' => [
            ...$baseConfig,
            'xml' => '<hello>world\'&quot;&lt;&gt;</hello>',
            'data' => 'world\'"<>',
        ];
        yield 'namespaced' => [
            ...$baseConfig,
            'context' => $context->withType(
                $namespacedType = $xsdType->withXmlTargetNamespace('http://example.com')
            ),
            'xml' => '<hello xmlns="http://example.com">world</hello>',
            'data' => 'world',
        ];
        yield 'namespaced-with-prefix' => [
            ...$baseConfig,
            'context' => $context->withType(
                $namespacedType->withXmlTargetNamespaceName('greet')
            ),
            'xml' => '<greet:hello xmlns:greet="http://example.com">world</greet:hello>',
            'data' => 'world',
        ];
        yield 'int-type' => [
            ...$baseConfig,
            'encoder' => $encoder = new ElementEncoder(new IntTypeEncoder()),
            'xml' => '<hello>32</hello>',
            'data' => 32,
        ];
        yield 'context-enhancing-child-encoder' => [
            ...$baseConfig,
            'encoder' => $encoder = new ElementEncoder(new class implements ElementContextEnhancer, XmlEncoder {
                public function iso(Context $context): Iso
                {
                    return (new IntTypeEncoder())->iso($context);
                }

                public function enhanceElementContext(Context $context): Context
                {
                    return $context->withType(
                        $context->type->withXmlTargetNodeName('bonjour')
                    );
                }
            }),
            'xml' => '<bonjour>32</bonjour>',
            'data' => 32,
        ];
        yield 'xsi-type-calculating-encoder' => [
            ...$baseConfig,
            'encoder' => $encoder = new ElementEncoder(new class implements ElementContextEnhancer, XmlEncoder, XsiTypeCalculator {
                public function iso(Context $context): Iso
                {
                    return (new IntTypeEncoder())->iso($context);
                }

                public function enhanceElementContext(Context $context): Context
                {
                    return $context->withBindingUse(BindingUse::ENCODED);
                }

                public function resolveXsiTypeForValue(Context $context, mixed $value): string
                {
                    return 'xsd:'.get_debug_type($value);
                }

                public function shouldIncludeXsiTargetNamespace(Context $context): bool
                {
                    return true;
                }
            }),
            'xml' => '<hello xmlns:xsd="http://www.w3.org/2001/XMLSchema" xsi:type="xsd:int" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">32</hello>',
            'data' => 32,
        ];
    }

    public function test_it_can_decode_from_xml_item(): void
    {
        $encoder = new ElementEncoder(new StringTypeEncoder());
        $context = self::createContext(
            $xsdType = XsdType::guess('string')
                ->withXmlTargetNodeName('hello')
                ->withMeta(static fn (TypeMeta $meta): TypeMeta => $meta->withIsQualified(true))
        );

        $item = Element::fromString('<hello>world</hello>');
        $iso = $encoder->iso($context);
        $actual = $iso->from($item);

        static::assertEquals('world', $actual);
    }
}
