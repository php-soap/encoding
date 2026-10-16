<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Encoder\AnyElementEncoder;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\ObjectAccess;
use Soap\Encoding\Encoder\ObjectEncoder;
use Soap\Encoding\EncoderRegistry;
use Soap\Encoding\Test\Fixture\Encoder\ThrowingLensEncoder;
use Soap\Encoding\Test\Unit\ContextCreatorTrait;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use stdClass;

#[CoversClass(ObjectEncoder::class)]
#[CoversClass(ObjectAccess::class)]
#[CoversClass(AnyElementEncoder::class)]
final class ObjectEncoderCustomLensTest extends TestCase
{
    use ContextCreatorTrait;

    private const SCHEMA = <<<XSD
        <complexType name="user">
            <sequence>
                <element name="id" type="xsd:int"/>
                <element name="custom" type="tns:customType"/>
                <any processContents="lax" minOccurs="0" maxOccurs="unbounded"/>
            </sequence>
        </complexType>
        <complexType name="customType">
            <simpleContent><extension base="xsd:string"/></simpleContent>
        </complexType>
        XSD;

    protected function setUp(): void
    {
        ThrowingLensEncoder::$encoderLensReads = 0;
        ThrowingLensEncoder::$decoderLensReads = 0;
    }

    public function test_it_decodes_through_custom_lenses_that_throw_on_a_missing_element(): void
    {
        $actual = $this->iso()->from('<user><id>1</id><extra>x</extra></user>');

        static::assertEquals((object) ['id' => 1, 'custom' => null, 'any' => ['<extra>x</extra>']], $actual);
        static::assertSame(1, ThrowingLensEncoder::$decoderLensReads);
    }

    public function test_it_decodes_through_custom_lenses_when_the_element_is_present(): void
    {
        $actual = $this->iso()->from('<user><id>1</id><custom>c</custom></user>');

        static::assertEquals((object) ['id' => 1, 'custom' => 'c', 'any' => []], $actual);
        static::assertSame(1, ThrowingLensEncoder::$decoderLensReads);
    }

    public function test_it_gives_custom_lenses_the_child_when_an_attribute_has_the_same_name(): void
    {
        $actual = $this->iso()->from('<user custom="attribute"><id>1</id><custom>c</custom></user>');

        static::assertEquals((object) ['id' => 1, 'custom' => 'c', 'any' => []], $actual);
        static::assertSame(1, ThrowingLensEncoder::$decoderLensReads);
    }

    public function test_it_keeps_any_children_when_an_attribute_has_the_same_name(): void
    {
        $actual = $this->iso()->from('<user type="attribute"><id>1</id><custom>c</custom><type>x</type></user>');

        static::assertEquals((object) ['id' => 1, 'custom' => 'c', 'any' => ['<type>x</type>']], $actual);
    }

    public function test_it_encodes_through_custom_lenses_that_throw_on_a_missing_property(): void
    {
        $actual = $this->iso()->to((object) ['id' => 1, 'any' => ['<extra>x</extra>']]);

        static::assertSame('<user><id>1</id><extra>x</extra></user>', $actual);
        static::assertSame(1, ThrowingLensEncoder::$encoderLensReads);
    }

    public function test_it_encodes_through_custom_lenses_when_the_property_is_present(): void
    {
        $actual = $this->iso()->to((object) ['id' => 1, 'custom' => 'c']);

        static::assertSame('<user><id>1</id><custom>c</custom></user>', $actual);
        static::assertSame(1, ThrowingLensEncoder::$encoderLensReads);
    }

    /**
     * @return \VeeWee\Reflecta\Iso\IsoInterface<object|array, string, mixed, mixed>
     */
    private function iso(): \VeeWee\Reflecta\Iso\IsoInterface
    {
        $metadata = self::createMetadataFromWsdl(self::SCHEMA, 'type="tns:user"', use: BindingUse::LITERAL);
        $registry = EncoderRegistry::default()
            ->addComplexTypeConverter('https://test', 'customType', new ThrowingLensEncoder());
        $context = new Context(
            $metadata->getTypes()->fetchByNameAndXmlNamespace('user', 'https://test')->getXsdType(),
            $metadata,
            $registry,
            self::buildNamespaces(),
        );

        return (new ObjectEncoder(stdClass::class))->iso($context);
    }
}
