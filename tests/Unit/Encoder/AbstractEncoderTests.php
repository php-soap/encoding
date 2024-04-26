<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder;

use PHPUnit\Framework\TestCase;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Test\Unit\ContextCreatorTrait;
use VeeWee\Xml\Dom\Document;
use function VeeWee\Xml\Dom\Configurator\trim_spaces;

abstract class AbstractEncoderTests extends TestCase
{
    use ContextCreatorTrait;

    /**
     * @return iterable<int, array{encoder: XmlEncoder, context: Context, xml: string, data: mixed}>
     */
    abstract public static function provideIsomorphicCases(): iterable;

    /**
     * @test
     * @dataProvider provideIsomorphicCases
     */
    public function it_can_decode_from_xml(XmlEncoder $encoder, Context $context, ?string $xml, mixed $data): void
    {
        $iso = $encoder->iso($context);
        $actual = $iso->from($xml);

        self::assertEquals($data, $actual);
    }

    /**
     * @test
     * @dataProvider provideIsomorphicCases
     */
    public function it_can_encode_into_xml(XmlEncoder $encoder, Context $context, ?string $xml, mixed $data): void
    {
        $iso = $encoder->iso($context);
        $actual = $iso->to($data);

        self::assertSame($xml, $actual);
    }
}
