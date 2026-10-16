<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\NullValueHandling;
use Soap\Encoding\Encoder\ObjectAccess;
use Soap\Encoding\Encoder\ObjectEncoder;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\EncoderRegistry;
use Soap\Encoding\Test\Fixture\Encoder\NullLensEncoder;
use Soap\Encoding\Test\Fixture\Encoder\ThrowingLensEncoder;
use Soap\Encoding\Test\Unit\ContextCreatorTrait;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use stdClass;

#[CoversClass(ObjectEncoder::class), CoversClass(ObjectAccess::class)]
final class ObjectPropertyPresenceTest extends TestCase
{
    use ContextCreatorTrait;

    private const SCHEMA = <<<'XML'
    <complexType name="testType">
        <sequence>
            <element name="note" type="xsd:string" minOccurs="0" nillable="true"/>
            <element name="requiredNote" type="xsd:string" nillable="true"/>
            <element name="child" minOccurs="0" maxOccurs="unbounded">
                <complexType><sequence>
                    <element name="note" type="xsd:string" minOccurs="0" nillable="true"/>
                </sequence></complexType>
            </element>
        </sequence>
    </complexType>
    <complexType name="eligibilityType">
        <sequence>
            <element name="optionalNillable" type="xsd:string" minOccurs="0" nillable="true"/>
            <element name="optionalNillableList" type="xsd:string" minOccurs="0" maxOccurs="unbounded" nillable="true"/>
            <element name="requiredNillable" type="xsd:string" nillable="true"/>
            <element name="optional" type="xsd:string" minOccurs="0"/>
            <element name="custom" type="tns:customType" minOccurs="0" nillable="true"/>
        </sequence>
        <attribute name="attribute" type="xsd:string"/>
    </complexType>
    <complexType name="textType">
        <simpleContent>
            <extension base="xsd:string">
                <attribute name="attribute" type="xsd:string"/>
            </extension>
        </simpleContent>
    </complexType>
    <complexType name="customType">
        <simpleContent><extension base="xsd:string"/></simpleContent>
    </complexType>
    XML;

    /** @return iterable<string, array{array|object, float, string, string}> */
    public static function property_values(): iterable
    {
        yield 'absent array property' => [[], 0.0, '', ''];
        yield 'null array property' => [['note' => null], 1.0, 'true', ''];
        yield 'present array property' => [['note' => 'text'], 1.0, '', 'text'];
        yield 'absent object property' => [(object) [], 0.0, '', ''];
        yield 'null object property' => [(object) ['note' => null], 1.0, 'true', ''];
        yield 'present object property' => [(object) ['note' => 'text'], 1.0, '', 'text'];
        yield 'uninitialized typed property' => [new class {
            public ?string $note;
        }, 0.0, '', ''];
        yield 'unset declared property' => [self::withUnsetNote(), 0.0, '', ''];
        yield 'property not declared on the class' => [new class {
            public ?string $other = null;
        }, 0.0, '', ''];
        yield 'lazy object whose initializer throws' => [self::failingLazyObject(), 1.0, 'true', ''];
    }

    #[Test, DataProvider('property_values')]
    public function optional_property_preserves_absence_null_and_value(array|object $value, float $count, string $nil, string $text): void
    {
        $xpath = self::encode($value);

        static::assertSame($count, $xpath->evaluate('count(/*/note)'));
        static::assertSame($nil, $xpath->evaluate('string(/*/note/@xsi:nil)'));
        static::assertSame($text, $xpath->evaluate('string(/*/note)'));
    }

    #[Test]
    public function repeated_children_preserve_independent_property_presence(): void
    {
        $xpath = self::encode(['child' => [[], ['note' => null], ['note' => 'text']]]);

        static::assertSame(3.0, $xpath->evaluate('count(/*/child)'));
        static::assertSame(0.0, $xpath->evaluate('count(/*/child[1]/note)'));
        static::assertSame('true', $xpath->evaluate('string(/*/child[2]/note/@xsi:nil)'));
        static::assertSame('text', $xpath->evaluate('string(/*/child[3]/note)'));
    }

    #[Test]
    public function absent_required_nullable_property_keeps_existing_nil_behavior(): void
    {
        $xpath = self::encode([]);

        static::assertSame('true', $xpath->evaluate('string(/*/requiredNote/@xsi:nil)'));
    }

    #[Test]
    public function only_optional_nillable_elements_with_the_default_lens_are_left_out_when_absent(): void
    {
        $eligibility = ObjectAccess::forContext(self::context('eligibilityType', new ThrowingLensEncoder()))->onNull;
        $text = ObjectAccess::forContext(self::context('textType'))->onNull;

        static::assertSame(
            [
                'attribute' => NullValueHandling::Write,
                'optionalNillable' => NullValueHandling::SkipWhenAbsent,
                'optionalNillableList' => NullValueHandling::SkipWhenAbsent,
                'requiredNillable' => NullValueHandling::Write,
                'optional' => NullValueHandling::Skip,
                'custom' => NullValueHandling::Write,
            ],
            $eligibility
        );
        static::assertSame(['attribute' => NullValueHandling::Write, '_' => NullValueHandling::Write], $text);
    }

    /**
     * A custom lens may read something else than the property of that name, so its null is never taken for absence.
     */
    #[Test, DataProvider('custom_lenses')]
    public function custom_lens_reading_null_keeps_writing_nil(XmlEncoder $customEncoder): void
    {
        $context = self::context('eligibilityType', $customEncoder);
        $xml = (new ObjectEncoder(stdClass::class))->iso($context)->to((object) ['requiredNillable' => 'r']);
        $xpath = self::xpath($xml);

        static::assertSame('true', $xpath->evaluate('string(/*/custom/@xsi:nil)'));
    }

    /** @return iterable<string, array{XmlEncoder}> */
    public static function custom_lenses(): iterable
    {
        yield 'lens throws on the absent property' => [new ThrowingLensEncoder()];
        yield 'lens returns null' => [new NullLensEncoder()];
    }

    private static function withUnsetNote(): object
    {
        $value = new class {
            public ?string $note = 'text';
        };
        unset($value->note);

        return $value;
    }

    private static function failingLazyObject(): object
    {
        $class = new class {
            public ?string $note = null;
        };

        return (new ReflectionClass($class))->newLazyGhost(
            static fn (): never => throw new RuntimeException('The initializer fails.')
        );
    }

    private static function encode(array|object $value): DOMXPath
    {
        $metadata = self::createMetadataFromWsdl(self::SCHEMA, 'type="tns:testType"');
        $context = self::createContextFromMetadata($metadata, 'testType')->withBindingUse(BindingUse::LITERAL);
        $iso = new ObjectEncoder(stdClass::class)->iso($context);

        return self::xpath($iso->to($value));
    }

    private static function context(string $typeName, ?XmlEncoder $customEncoder = null): Context
    {
        $metadata = self::createMetadataFromWsdl(self::SCHEMA, 'type="tns:'.$typeName.'"');
        $registry = EncoderRegistry::default();
        if ($customEncoder !== null) {
            $registry = $registry->addComplexTypeConverter('https://test', 'customType', $customEncoder);
        }

        return new Context(
            $metadata->getTypes()->fetchByNameAndXmlNamespace($typeName, 'https://test')->getXsdType(),
            $metadata,
            $registry,
            self::buildNamespaces(),
            BindingUse::LITERAL,
        );
    }

    private static function xpath(string $xml): DOMXPath
    {
        $document = new DOMDocument();

        static::assertTrue($document->loadXML($xml));

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance');

        return $xpath;
    }
}
