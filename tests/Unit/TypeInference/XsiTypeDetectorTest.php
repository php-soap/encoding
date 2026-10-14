<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\TypeInference;

use Dom\Element;
use Dom\XMLDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Test\Unit\ContextCreatorTrait;
use Soap\Encoding\TypeInference\XsiTypeDetector;
use Soap\Encoding\Xml\Node\Element as XmlElement;
use Soap\Engine\Metadata\Model\TypeMeta;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(XsiTypeDetector::class)]
final class XsiTypeDetectorTest extends TestCase
{
    use ContextCreatorTrait;

    private const ITEM_TYPE_SCHEMA = <<<EOXML
        <complexType name="itemType">
            <sequence>
                <element name="id" type="xsd:int"/>
            </sequence>
        </complexType>
        EOXML;

    public function test_it_returns_none_when_no_xsi_type_attribute(): void
    {
        $element = $this->createElement('<element xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>');
        $context = self::createContext(XsdType::guess('anyType'));

        $result = XsiTypeDetector::detectXsdTypeFromXmlElement($context, $element);

        static::assertFalse($result->isSome());
    }

    public function test_it_detects_xsi_type_with_matching_prefix(): void
    {
        $element = $this->createElement(
            '<element xmlns:tns="https://test" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="tns:MyType"/>'
        );
        $context = self::createContext(XsdType::guess('anyType'));

        $result = XsiTypeDetector::detectXsdTypeFromXmlElement($context, $element);

        static::assertTrue($result->isSome());
        $type = $result->unwrap();
        static::assertSame('MyType', $type->getXmlTypeName());
        static::assertSame('https://test', $type->getXmlNamespace());
    }

    public function test_it_detects_xsi_type_with_mismatched_prefix(): void
    {
        $element = $this->createElement(
            '<element xmlns:ns2="https://test" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="ns2:MyType"/>'
        );
        $context = self::createContext(XsdType::guess('anyType'));

        $result = XsiTypeDetector::detectXsdTypeFromXmlElement($context, $element);

        static::assertTrue($result->isSome());
        $type = $result->unwrap();
        static::assertSame('MyType', $type->getXmlTypeName());
        static::assertSame('https://test', $type->getXmlNamespace());
    }

    /**
     * Servers that omit xmlns:xsi but still produce xsi:type attributes.
     * Falls back to getAttribute('xsi:type').
     */
    public function test_it_detects_xsi_type_without_xsi_namespace_declaration(): void
    {
        $doc = XMLDocument::createFromString('<element xmlns:tns="https://test" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="tns:MyType"/>');
        $element = $doc->documentElement;

        $context = self::createContext(XsdType::guess('anyType'));
        $result = XsiTypeDetector::detectXsdTypeFromXmlElement($context, $element);

        static::assertTrue($result->isSome());
        $type = $result->unwrap();
        static::assertSame('MyType', $type->getXmlTypeName());
        static::assertSame('https://test', $type->getXmlNamespace());
    }

    /**
     * Unprefixed xsi:type value resolves via the element's default namespace.
     */
    public function test_it_detects_unprefixed_xsi_type_via_default_namespace(): void
    {
        $element = $this->createElement(
            '<element xmlns="https://test" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="MyType"/>'
        );
        $context = self::createContext(XsdType::guess('anyType'));

        $result = XsiTypeDetector::detectXsdTypeFromXmlElement($context, $element);

        static::assertTrue($result->isSome());
        $type = $result->unwrap();
        static::assertSame('MyType', $type->getXmlTypeName());
        static::assertSame('https://test', $type->getXmlNamespace());
    }

    /**
     * Unprefixed xsi:type without any default namespace returns none.
     */
    public function test_it_returns_none_for_unprefixed_xsi_type_without_default_namespace(): void
    {
        $element = $this->createElement(
            '<element xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="MyType"/>'
        );
        $context = self::createContext(XsdType::guess('anyType'));

        $result = XsiTypeDetector::detectXsdTypeFromXmlElement($context, $element);

        static::assertFalse($result->isSome());
    }

    /**
     * Exercises the WSDL fallback path: prefix exists in WSDL Namespaces but NOT in the DOM.
     */
    public function test_it_falls_back_to_wsdl_namespaces_when_dom_has_no_declaration(): void
    {
        $doc = XMLDocument::createFromString('<element xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>');
        $element = $doc->documentElement;
        $element->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:type', 'tns:MyType');

        $context = self::createContext(XsdType::guess('anyType'));
        $result = XsiTypeDetector::detectXsdTypeFromXmlElement($context, $element);

        static::assertTrue($result->isSome());
        $type = $result->unwrap();
        static::assertSame('MyType', $type->getXmlTypeName());
        static::assertSame('https://test', $type->getXmlNamespace());
    }

    public function test_it_returns_none_for_unknown_prefix(): void
    {
        $element = $this->createElement(
            '<element xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="unknown:MyType"/>'
        );
        $context = self::createContext(XsdType::guess('anyType'));

        $result = XsiTypeDetector::detectXsdTypeFromXmlElement($context, $element);

        static::assertFalse($result->isSome());
    }

    public function test_it_decodes_a_complex_xsi_type_inside_an_any_type_slot(): void
    {
        $metadata = self::createMetadataFromWsdl(self::ITEM_TYPE_SCHEMA, 'type="tns:itemType"');
        $context = self::createContextFromMetadata($metadata, 'itemType')
            ->withType(XsdType::any()->withMeta(static fn (TypeMeta $meta): TypeMeta => $meta->withIsElement(true)));

        $result = $this->decodeWithDetectedEncoder(
            $context,
            '<item xmlns:tns="https://test" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="tns:itemType"><id>1</id></item>'
        );

        static::assertEquals((object) ['id' => 1], $result);
    }

    public function test_it_decodes_a_soap_enc_array_xsi_type_inside_an_any_type_slot(): void
    {
        $metadata = self::createMetadataFromWsdl(self::ITEM_TYPE_SCHEMA, 'type="tns:itemType"');
        $context = self::createContextFromMetadata($metadata, 'itemType')
            ->withType(XsdType::any()->withMeta(static fn (TypeMeta $meta): TypeMeta => $meta->withIsElement(true)));

        $result = $this->decodeWithDetectedEncoder(
            $context,
            '<item xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"'
            . ' xsi:type="SOAP-ENC:Array" SOAP-ENC:arrayType="xsd:int[2]"><item xsi:type="xsd:int">1</item><item xsi:type="xsd:int">2</item></item>'
        );

        static::assertSame([1, 2], $result);
    }

    public function test_it_decodes_a_simple_xsi_type_inside_a_complex_slot(): void
    {
        $metadata = self::createMetadataFromWsdl(self::ITEM_TYPE_SCHEMA, 'type="tns:itemType"');
        $context = self::createContextFromMetadata($metadata, 'itemType');

        $result = $this->decodeWithDetectedEncoder(
            $context,
            '<item xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="xsd:string">abc</item>'
        );

        static::assertSame('abc', $result);
    }

    private function decodeWithDetectedEncoder(Context $context, string $xml): mixed
    {
        $element = $this->createElement($xml);

        return XsiTypeDetector::detectEncoderFromXmlElement($context, $element)
            ->unwrap()
            ->iso($context)
            ->from(XmlElement::fromDOMElement($element));
    }

    private function createElement(string $xml): Element
    {
        $doc = XMLDocument::createFromString($xml);

        return $doc->documentElement;
    }
}
