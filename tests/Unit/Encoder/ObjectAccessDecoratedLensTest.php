<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\ObjectAccess;
use Soap\Encoding\Encoder\ObjectEncoder;
use Soap\Encoding\Encoder\OptionalElementEncoder;
use Soap\Encoding\Encoder\RepeatingElementEncoder;
use Soap\Encoding\Encoder\XsiTypeEncoder;
use Soap\Encoding\EncoderRegistry;
use Soap\Encoding\Test\Fixture\Encoder\ThrowingLensEncoder;
use Soap\Encoding\Test\Fixture\Encoder\XsiDetectedLensEncoder;
use Soap\Encoding\Test\Unit\ContextCreatorTrait;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use stdClass;
use VeeWee\Reflecta\Iso\IsoInterface;

/**
 * Custom object lenses are found behind every encoder that the encoder detector wraps around a custom encoder.
 */
#[CoversClass(ObjectAccess::class)]
#[CoversClass(OptionalElementEncoder::class)]
#[CoversClass(RepeatingElementEncoder::class)]
#[CoversClass(XsiTypeEncoder::class)]
final class ObjectAccessDecoratedLensTest extends TestCase
{
    use ContextCreatorTrait;

    private const SCHEMA = <<<XSD
        <complexType name="user">
            <sequence>
                <element name="id" type="xsd:int"/>
                <element name="optional" type="tns:customType" minOccurs="0"/>
                <element name="repeating" type="tns:customType" maxOccurs="unbounded"/>
                <element name="xsi" type="tns:xsiType"/>
            </sequence>
        </complexType>
        <complexType name="customType">
            <simpleContent><extension base="xsd:string"/></simpleContent>
        </complexType>
        <complexType name="xsiType">
            <simpleContent><extension base="xsd:string"/></simpleContent>
        </complexType>
        XSD;

    protected function setUp(): void
    {
        ThrowingLensEncoder::$encoderLensReads = 0;
        ThrowingLensEncoder::$decoderLensReads = 0;
    }

    public function test_it_encodes_through_custom_lenses_behind_decorating_encoders(): void
    {
        $actual = $this->iso()->to((object) [
            'id' => 1,
            'optional' => 'o',
            'repeating' => ['r1', 'r2'],
            'xsi' => 'x',
        ]);

        static::assertSame(
            '<user><id>1</id><custom>o</custom><custom>r1</custom><custom>r2</custom><custom>x</custom></user>',
            $actual
        );
        static::assertSame(3, ThrowingLensEncoder::$encoderLensReads);
    }

    public function test_it_decodes_through_custom_lenses_behind_decorating_encoders(): void
    {
        $actual = $this->iso()->from(
            '<user><id>1</id><optional>o</optional><repeating>r1</repeating><repeating>r2</repeating><xsi>x</xsi></user>'
        );

        static::assertEquals(
            (object) ['id' => 1, 'optional' => 'o', 'repeating' => ['r1', 'r2'], 'xsi' => 'x'],
            $actual
        );
        static::assertSame(3, ThrowingLensEncoder::$decoderLensReads);
    }

    #[DataProvider('provideDecoratedProperties')]
    public function test_each_custom_encoder_sits_behind_the_decorator_under_test(string $property, string $decorator): void
    {
        $context = $this->context();
        $propertyType = ObjectAccess::forContext($context)->properties[$property]->getType();
        $encoder = $context->registry->detectEncoderForContext($context->withType($propertyType));

        static::assertInstanceOf($decorator, $encoder->decoratedEncoder());
    }

    /**
     * @return iterable<string, array{string, class-string}>
     */
    public static function provideDecoratedProperties(): iterable
    {
        yield 'optional' => ['optional', OptionalElementEncoder::class];
        yield 'repeating' => ['repeating', RepeatingElementEncoder::class];
        yield 'xsi' => ['xsi', XsiTypeEncoder::class];
    }

    private function context(): Context
    {
        $metadata = self::createMetadataFromWsdl(self::SCHEMA, 'type="tns:user"', use: BindingUse::LITERAL);
        $registry = EncoderRegistry::default()
            ->addComplexTypeConverter('https://test', 'customType', new ThrowingLensEncoder())
            ->addComplexTypeConverter('https://test', 'xsiType', new XsiDetectedLensEncoder());

        return new Context(
            $metadata->getTypes()->fetchByNameAndXmlNamespace('user', 'https://test')->getXsdType(),
            $metadata,
            $registry,
            self::buildNamespaces(),
        );
    }

    /**
     * @return IsoInterface<object|array, string, mixed, mixed>
     */
    private function iso(): IsoInterface
    {
        return (new ObjectEncoder(stdClass::class))->iso($this->context());
    }
}
