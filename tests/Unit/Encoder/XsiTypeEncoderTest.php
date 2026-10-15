<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\ElementEncoder;
use Soap\Encoding\Encoder\SimpleType\StringTypeEncoder;
use Soap\Encoding\Encoder\XsiTypeEncoder;
use Soap\Encoding\Test\Unit\ContextCreatorTrait;
use Soap\Encoding\Xml\Node\Element;
use Soap\Engine\Metadata\Model\TypeMeta;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(XsiTypeEncoder::class)]
final class XsiTypeEncoderTest extends TestCase
{
    use ContextCreatorTrait;

    public function test_it_decodes_through_the_detected_xsi_type(): void
    {
        $actual = $this->decode(
            '<hello xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="xsd:int">32</hello>'
        );

        static::assertSame(32, $actual);
    }

    public function test_it_decodes_an_element_without_attributes_through_the_inner_encoder(): void
    {
        $actual = $this->decode('<hello>32</hello>');

        static::assertSame('32', $actual);
    }

    public function test_it_decodes_an_element_with_attributes_but_no_xsi_type_through_the_inner_encoder(): void
    {
        $actual = $this->decode('<hello xmlns:xsd="http://www.w3.org/2001/XMLSchema">32</hello>');

        static::assertSame('32', $actual);
    }

    private function decode(string $xml): mixed
    {
        $encoder = new XsiTypeEncoder(new ElementEncoder(new StringTypeEncoder()));

        return $encoder->iso($this->createStringContext())->from(Element::fromString($xml));
    }

    private function createStringContext(): Context
    {
        return self::createContext(
            XsdType::guess('string')
                ->withXmlNamespace('http://www.w3.org/2001/XMLSchema')
                ->withXmlTargetNodeName('hello')
                ->withMeta(static fn (TypeMeta $meta): TypeMeta => $meta->withIsQualified(true)->withIsSimple(true)->withIsElement(true))
        );
    }
}
