<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\TypeInference;

use Dom\Element;
use Dom\XMLDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Test\Unit\ContextCreatorTrait;
use Soap\Encoding\TypeInference\XsiTypeDetector;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(XsiTypeDetector::class)]
final class XsiTypeDetectorTest extends TestCase
{
    use ContextCreatorTrait;

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

    private function createElement(string $xml): Element
    {
        $doc = XMLDocument::createFromString($xml);

        return $doc->documentElement;
    }
}
