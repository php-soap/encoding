<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\SimpleType;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Encoder\ElementEncoder;
use Soap\Encoding\Encoder\SimpleType\StringTypeEncoder;
use Soap\Encoding\Restriction\WhitespaceRestriction;
use Soap\Encoding\Test\Unit\Encoder\AbstractEncoderTests;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(StringTypeEncoder::class)]
#[CoversClass(WhitespaceRestriction::class)]
final class StringTypeEncoderTest extends AbstractEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => $encoder = new StringTypeEncoder(),
            'context' => $context = self::createContext(
                XsdType::guess('string')
                    ->withXmlTargetNodeName('root')
            ),
        ];

        yield 'simple' => [
            ...$baseConfig,
            'xml' => 'hello',
            'data' => 'hello',
        ];
        yield 'special-chars' => [
            ...$baseConfig,
            'xml' => 'hëllo\'"<>',
            'data' => 'hëllo\'"<>',
        ];

        $elementEncoder = new ElementEncoder($encoder);
        yield 'element-wrapped' => [
            ...$baseConfig,
            'encoder' => $elementEncoder,
            'xml' => '<root>hello</root>',
            'data' => 'hello',

        ];
        yield 'element-wrapped-special-chars' => [
            ...$baseConfig,
            'encoder' => $elementEncoder,
            'xml' => '<root>hëllo\'&quot;&lt;&gt;</root>',
            'data' => 'hëllo\'"<>',
        ];
    }
}
