<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Encoder\ElementEncoder;
use Soap\Encoding\Encoder\SimpleType\StringTypeEncoder;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(ElementEncoder::class)]
class ElementEncoderTest extends AbstractEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => $encoder = new ElementEncoder(new StringTypeEncoder()),
            'context' => $context = self::createContext(
                $xsdType = XsdType::guess('string')
                    ->withXmlTargetNodeName('hello')
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
    }
}
