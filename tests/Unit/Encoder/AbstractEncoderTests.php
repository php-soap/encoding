<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Test\Unit\ContextCreatorTrait;

abstract class AbstractEncoderTests extends TestCase
{
    use ContextCreatorTrait;

    /**
     * @return iterable<int, array{encoder: XmlEncoder, context: Context, xml: string, data: mixed}>
     */
    abstract public static function provideIsomorphicCases(): iterable;

    #[DataProvider('provideIsomorphicCases')]
    public function test_it_can_decode_from_xml(XmlEncoder $encoder, Context $context, ?string $xml, mixed $data): void
    {
        $iso = $encoder->iso($context);
        $actual = $iso->from($xml);

        static::assertEquals($data, $actual);
    }

    #[DataProvider('provideIsomorphicCases')]
    public function test_it_can_encode_into_xml(XmlEncoder $encoder, Context $context, ?string $xml, mixed $data): void
    {
        $iso = $encoder->iso($context);
        $actual = $iso->to($data);

        static::assertSame($xml, $actual);
    }
}
