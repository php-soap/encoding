<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\Feature\DisregardXsiInformation;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Encoder\XsiTypeEncoder;
use Soap\Encoding\Test\Unit\ContextCreatorTrait;
use Soap\Encoding\TypeInference\XsiTypeDetector;
use Soap\Encoding\Xml\Node\Element;
use Soap\Engine\Metadata\Model\TypeMeta;
use Soap\Engine\Metadata\Model\XsdType;
use VeeWee\Reflecta\Iso\Iso;

#[CoversClass(XsiTypeEncoder::class)]
#[CoversClass(XsiTypeDetector::class)]
final class XsiTypeEncoderTest extends TestCase
{
    use ContextCreatorTrait;

    private const NAMESPACES = 'xmlns:tns="https://test" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"';

    public function test_one_iso_decodes_every_element_by_its_own_xsi_type(): void
    {
        $context = self::createContext(
            XsdType::any()->withMeta(static fn (TypeMeta $meta): TypeMeta => $meta->withIsElement(true))
        );
        $iso = $context->registry->detectEncoderForContext($context)->iso($context);

        static::assertSame(1, $iso->from($this->element('<item ' . self::NAMESPACES . ' xsi:type="xsd:int">1</item>')));
        static::assertSame('1', $iso->from($this->element('<item ' . self::NAMESPACES . ' xsi:type="xsd:string">1</item>')));
        static::assertTrue($iso->from($this->element('<item ' . self::NAMESPACES . ' xsi:type="xsd:boolean">true</item>')));
    }

    public function test_one_iso_falls_back_to_the_declared_type_for_elements_without_xsi_type(): void
    {
        $schema = <<<EOXML
        <complexType name="itemType">
            <sequence>
                <element name="id" type="xsd:int"/>
            </sequence>
        </complexType>
        EOXML;
        $metadata = self::createMetadataFromWsdl($schema, 'type="tns:itemType"');
        $context = self::createContextFromMetadata($metadata, 'itemType');
        $iso = $context->registry->detectEncoderForContext($context)->iso($context);

        static::assertEquals((object) ['id' => 1], $iso->from($this->element('<item ' . self::NAMESPACES . '><id>1</id></item>')));
        static::assertSame('abc', $iso->from($this->element('<item ' . self::NAMESPACES . ' xsi:type="xsd:string">abc</item>')));
        static::assertEquals((object) ['id' => 2], $iso->from($this->element('<item ' . self::NAMESPACES . '><id>2</id></item>')));
    }

    public function test_it_ignores_xsi_type_for_encoders_that_disregard_it(): void
    {
        $context = self::createContext(
            XsdType::any()->withMeta(static fn (TypeMeta $meta): TypeMeta => $meta->withIsElement(true))
        );
        $iso = (new XsiTypeEncoder(new DisregardingEncoder()))->iso($context);

        static::assertSame('inner', $iso->from($this->element('<item ' . self::NAMESPACES . ' xsi:type="xsd:int">1</item>')));
    }

    private function element(string $xml): Element
    {
        return Element::fromString($xml);
    }
}

/**
 * @implements XmlEncoder<mixed, mixed, string, Element|string>
 */
final class DisregardingEncoder implements DisregardXsiInformation, XmlEncoder
{
    /**
     * @return Iso<mixed, mixed, string, Element|string>
     */
    public function iso(Context $context): Iso
    {
        return new Iso(
            static fn (mixed $value): string => '<item/>',
            static fn (Element|string $value): string => 'inner',
        );
    }
}
