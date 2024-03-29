<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\SimpleType;


use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Encoder\SimpleType\Base64BinaryTypeEncoder;
use Soap\Encoding\Test\Unit\Encoder\AbstractEncoderTests;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(Base64BinaryTypeEncoder::class)]
class Base64BinaryTypeEncoderTest extends AbstractEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => $encoder = new Base64BinaryTypeEncoder(),
            'context' => $context = self::createContext(XsdType::guess('base64Binary')),
        ];

        yield 'simple' => [
            ...$baseConfig,
            'xml' => base64_encode('hello'),
            'data' => 'hello',
        ];
    }
}
