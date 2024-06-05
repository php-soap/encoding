<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Encoder\ElementEncoder;
use Soap\Encoding\Encoder\ObjectEncoder;
use Soap\Encoding\Encoder\OptionalElementEncoder;
use Soap\Encoding\Encoder\SimpleType\IntTypeEncoder;
use Soap\Encoding\Encoder\SimpleType\StringTypeEncoder;
use Soap\Engine\Metadata\Model\TypeMeta;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(OptionalElementEncoder::class)]
class OptionalElementEncoderTest extends AbstractEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => $encoder = new OptionalElementEncoder(new ElementEncoder(new StringTypeEncoder())),
            'context' => $context = self::createContext(
                $xsdType = XsdType::guess('string')
                    ->withXmlTargetNodeName('hello')
                    ->withMeta(static fn (TypeMeta $meta): TypeMeta => $meta
                        ->withIsNullable(true)
                        ->withMinOccurs(0)
                        ->withMaxOccurs(1)
                        ->withIsSimple(true)
                        ->withIsElement(true)
                        ->withIsQualified(true)
                    )
            ),
        ];

        yield 'with-null-value' => [
            ...$baseConfig,
            'xml' => '',
            'data' => null,
        ];
        yield 'with-empty-value' => [
            ...$baseConfig,
            'xml' => '<hello></hello>',
            'data' => '',
        ];
        yield 'with-nil-value' => [
            ...$baseConfig,
            'context' => $context->withType(
                $xsdType->withMeta(static fn (TypeMeta $meta): TypeMeta => $meta
                    ->withIsNil(true)
                )
            ),
            'xml' => '<hello xsi:nil="true" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>',
            'data' => null,
        ];
        yield 'with-value' => [
            ...$baseConfig,
            'xml' => '<hello>world</hello>',
            'data' => 'world',
        ];
        yield 'with-non-nullable-value' => [
            ...$baseConfig,
            'context' => $context->withType(
                $xsdType->withMeta(static fn (TypeMeta $meta): TypeMeta => $meta
                    ->withIsNullable(false)
                )
            ),
            'xml' => '<hello>world</hello>',
            'data' => 'world',
        ];
        yield 'with-int-value' => [
            ...$baseConfig,
            'encoder' => new OptionalElementEncoder(new ElementEncoder(new IntTypeEncoder())),
            'xml' => '<hello>42</hello>',
            'data' => 42,
        ];

        $objectContext = self::createContextFromMetadata(ObjectEncoderTest::createWsdlExample(), 'user');
        yield 'with-object-value' => [
            ...$baseConfig,
            'encoder' => new OptionalElementEncoder(new ObjectEncoder(\stdClass::class)),
            'context' => $objectContext,
            'xml' => '<tns:user xmlns:tns="https://test"><tns:active xmlns:tns="https://test">true</tns:active><tns:hat xmlns:tns="https://test"><tns:color xmlns:tns="https://test">green</tns:color></tns:hat></tns:user>',
            'data' => (object)[
                'active' => true,
                'hat' => (object)[
                    'color' => 'green',
                ],
            ],
        ];
        yield 'with-empty-object-value' => [
            ...$baseConfig,
            'encoder' => new OptionalElementEncoder(new ObjectEncoder(\stdClass::class)),
            'context' => $objectContext->withType(
                $objectContext->type->withMeta(static fn (TypeMeta $meta): TypeMeta => $meta
                    ->withIsNullable(true)
                    ->withMinOccurs(0)
                    ->withMaxOccurs(1)
                )
            ),
            'xml' => '',
            'data' => null,
        ];
    }
}
