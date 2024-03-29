<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Encoder\ExtendedSimpleTypeEncoder;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(ExtendedSimpleTypeEncoder::class)]
class ExtendedSimpleTypeEncoderTest extends AbstractEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => $encoder = new ExtendedSimpleTypeEncoder(),
            'context' => $context = self::createContext(
                $xsdType = XsdType::guess('string')
            ),
        ];

        // TODO - figure out what this was again ... :(
        return [];
    }
}
