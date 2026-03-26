<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\TypeInference;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Test\Unit\ContextCreatorTrait;
use Soap\Encoding\TypeInference\XsiTypeDetector;
use Soap\Encoding\Xml\Node\Element;

/**
 * Verifies that XsiTypeDetector's cache returns correct results when the same
 * xsi:type is encountered with different calling context meta (nullable, isList, etc.).
 */
#[CoversClass(XsiTypeDetector::class)]
final class XsiTypeDetectorCacheTest extends TestCase
{
    use ContextCreatorTrait;

    /**
     * Two properties with the same xsi:type (xsd:string) but one nullable, one not.
     * The cache must not return a stale FixedIsoEncoder from the first call.
     */
    public function test_same_xsi_type_with_different_nullable_meta_decodes_correctly(): void
    {
        $xml = <<<XML
        <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns="https://test"
                           xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                           xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/">
            <SOAP-ENV:Body SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                <tns:test>
                    <testParam xsi:type="tns:testType">
                        <requiredField xsi:type="xsd:string">hello</requiredField>
                        <nullableField xsi:type="xsd:string">world</nullableField>
                    </testParam>
                </tns:test>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
        XML;

        $schema = <<<EOXML
        <complexType name="testType">
            <sequence>
                <element name="requiredField" type="xsd:string"/>
                <element name="nullableField" type="xsd:string" nillable="true"/>
            </sequence>
        </complexType>
        EOXML;

        $metadata = self::createMetadataFromWsdl($schema, 'type="tns:testType"');
        $context = self::createContextFromMetadata($metadata, 'testType');
        $encoder = $context->registry->detectEncoderForContext($context);
        $result = $encoder->iso($context)->from(
            Element::fromString(
                '<testParam xsi:type="tns:testType" xmlns:tns="https://test" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
                . '<requiredField xsi:type="xsd:string">hello</requiredField>'
                . '<nullableField xsi:type="xsd:string">world</nullableField>'
                . '</testParam>'
            )
        );

        static::assertIsObject($result);
        static::assertSame('hello', $result->requiredField);
        static::assertSame('world', $result->nullableField);
    }

    /**
     * Decoding the same xsi:type across two completely different registries
     * must not share cache entries (WeakMap scoped to registry).
     */
    public function test_different_registries_do_not_share_cache(): void
    {
        $schema = <<<EOXML
        <complexType name="testType">
            <sequence>
                <element name="value" type="xsd:string"/>
            </sequence>
        </complexType>
        EOXML;

        $metadata1 = self::createMetadataFromWsdl($schema, 'type="tns:testType"');
        $metadata2 = self::createMetadataFromWsdl($schema, 'type="tns:testType"');

        $context1 = self::createContextFromMetadata($metadata1, 'testType');
        $context2 = self::createContextFromMetadata($metadata2, 'testType');

        // Different registries
        static::assertNotSame($context1->registry, $context2->registry);

        $element = Element::fromString(
            '<testParam xsi:type="tns:testType" xmlns:tns="https://test" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<value xsi:type="xsd:string">test</value>'
            . '</testParam>'
        );

        $encoder1 = $context1->registry->detectEncoderForContext($context1);
        $encoder2 = $context2->registry->detectEncoderForContext($context2);

        $result1 = $encoder1->iso($context1)->from($element);
        $result2 = $encoder2->iso($context2)->from($element);

        static::assertSame('test', $result1->value);
        static::assertSame('test', $result2->value);
    }

    /**
     * Same xsi:type used as both a list element and a non-list element.
     */
    public function test_same_xsi_type_with_list_and_non_list_decodes_correctly(): void
    {
        $schema = <<<EOXML
        <complexType name="testType">
            <sequence>
                <element name="single" type="xsd:string"/>
                <element name="multi" type="xsd:string" maxOccurs="unbounded"/>
            </sequence>
        </complexType>
        EOXML;

        $metadata = self::createMetadataFromWsdl($schema, 'type="tns:testType"');
        $context = self::createContextFromMetadata($metadata, 'testType');
        $encoder = $context->registry->detectEncoderForContext($context);

        $result = $encoder->iso($context)->from(
            Element::fromString(
                '<testParam xsi:type="tns:testType" xmlns:tns="https://test" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
                . '<single xsi:type="xsd:string">one</single>'
                . '<multi xsi:type="xsd:string">a</multi>'
                . '<multi xsi:type="xsd:string">b</multi>'
                . '</testParam>'
            )
        );

        static::assertIsObject($result);
        static::assertSame('one', $result->single);
        static::assertSame(['a', 'b'], $result->multi);
    }
}
