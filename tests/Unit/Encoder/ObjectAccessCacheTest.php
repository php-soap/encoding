<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\ObjectAccess;
use Soap\Encoding\EncoderRegistry;
use Soap\Encoding\Test\Unit\ContextCreatorTrait;
use Soap\WsdlReader\Model\Definitions\BindingUse;

/**
 * Verifies that ObjectAccess::forContext() cache returns correct results
 * when the same type is used with different contexts.
 */
#[CoversClass(ObjectAccess::class)]
final class ObjectAccessCacheTest extends TestCase
{
    use ContextCreatorTrait;

    private const SCHEMA = <<<'EOXML'
    <complexType name="testType">
        <sequence>
            <element name="name" type="xsd:string"/>
            <element name="value" type="xsd:int"/>
        </sequence>
    </complexType>
    EOXML;

    /**
     * Same type with literal vs encoded binding must produce different ObjectAccess
     * instances (the isos capture bindingUse-dependent behavior).
     */
    public function test_different_binding_use_produces_different_object_access(): void
    {
        $metadata = self::createMetadataFromWsdl(self::SCHEMA, 'type="tns:testType"');
        $type = $metadata->getTypes()->fetchFirstByName('testType');
        $registry = EncoderRegistry::default();
        $namespaces = self::buildNamespaces();

        $literalContext = new Context($type->getXsdType(), $metadata, $registry, $namespaces, BindingUse::LITERAL);
        $encodedContext = new Context($type->getXsdType(), $metadata, $registry, $namespaces, BindingUse::ENCODED);

        $literalAccess = ObjectAccess::forContext($literalContext);
        $encodedAccess = ObjectAccess::forContext($encodedContext);

        // Both should have the same properties
        static::assertSame(
            array_keys($literalAccess->properties),
            array_keys($encodedAccess->properties)
        );

        // But they must be different instances (different isos due to bindingUse)
        static::assertNotSame($literalAccess, $encodedAccess);

        // Encoded adds xsi:type attribute, literal does not
        $literalXml = $literalAccess->isos['name']->to('hello');
        $encodedXml = $encodedAccess->isos['name']->to('hello');

        static::assertStringNotContainsString('xsi:type', $literalXml);
        static::assertStringContainsString('xsi:type', $encodedXml);
    }

    /**
     * Same type on different registries must not share cache entries.
     */
    public function test_different_registries_produce_separate_cache_entries(): void
    {
        $metadata = self::createMetadataFromWsdl(self::SCHEMA, 'type="tns:testType"');
        $type = $metadata->getTypes()->fetchFirstByName('testType');
        $namespaces = self::buildNamespaces();

        $context1 = new Context($type->getXsdType(), $metadata, EncoderRegistry::default(), $namespaces);
        $context2 = new Context($type->getXsdType(), $metadata, EncoderRegistry::default(), $namespaces);

        static::assertNotSame($context1->registry, $context2->registry);

        $access1 = ObjectAccess::forContext($context1);
        $access2 = ObjectAccess::forContext($context2);

        // Both valid, same structure, but different instances (different registries)
        static::assertNotSame($access1, $access2);
        static::assertSame(array_keys($access1->properties), array_keys($access2->properties));
    }

    /**
     * Repeated calls with the same context return the cached instance.
     */
    public function test_same_context_returns_cached_instance(): void
    {
        $metadata = self::createMetadataFromWsdl(self::SCHEMA, 'type="tns:testType"');
        $context = self::createContextFromMetadata($metadata, 'testType');

        $access1 = ObjectAccess::forContext($context);
        $access2 = ObjectAccess::forContext($context);

        static::assertSame($access1, $access2);
    }
}
