<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Encoder\ObjectAccess;
use Soap\Encoding\Encoder\ObjectEncoder;
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

    private static function encode(array|object $value): DOMXPath
    {
        $metadata = self::createMetadataFromWsdl(self::SCHEMA, 'type="tns:testType"');
        $context = self::createContextFromMetadata($metadata, 'testType')->withBindingUse(BindingUse::LITERAL);
        $iso = new ObjectEncoder(stdClass::class)->iso($context);

        $xml = $iso->to($value);
        $document = new DOMDocument();

        static::assertTrue($document->loadXML($xml));

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance');

        return $xpath;
    }
}
