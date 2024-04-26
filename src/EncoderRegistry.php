<?php
declare(strict_types=1);

namespace Soap\Encoding;

use Psl\Collection\MutableMap;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\ElementEncoder;
use Soap\Encoding\Encoder\EncoderDetector;
use Soap\Encoding\Encoder\ObjectEncoder;
use Soap\Encoding\Encoder\OptionalElementEncoder;
use Soap\Encoding\Encoder\SimpleType;
use Soap\Encoding\Encoder\SimpleType\Base64BinaryTypeEncoder;
use Soap\Encoding\Encoder\SimpleType\BoolTypeEncoder;
use Soap\Encoding\Encoder\SimpleType\FloatTypeEncoder;
use Soap\Encoding\Encoder\SimpleType\IntTypeEncoder;
use Soap\Encoding\Encoder\SimpleType\ScalarTypeEncoder;
use Soap\Encoding\Encoder\SimpleType\StringTypeEncoder;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Formatter\QNameFormatter;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\Xml\Xmlns;

final class EncoderRegistry
{
    /**
     * @param MutableMap<string, XmlEncoder> $simpleTypeMap
     * @param MutableMap<string, XmlEncoder> $complextTypeMap
     */
    private function __construct(
        private MutableMap $simpleTypeMap,
        private MutableMap $complextTypeMap
    ) {
    }

    public static function default(): self
    {
        $qNameFormatter = new QNameFormatter();
        $xsd = Xmlns::xsd()->value();
        $xsd1999 = '.????';

        return new self(
            new MutableMap([
                // Strings:
                $qNameFormatter($xsd, 'string') => new StringTypeEncoder(),
                $qNameFormatter($xsd, 'anyURI') => new StringTypeEncoder(),
                $qNameFormatter($xsd, 'qname') => new StringTypeEncoder(),
                $qNameFormatter($xsd, 'NOTATION') => new StringTypeEncoder(),
                $qNameFormatter($xsd, 'normalizedString') => new StringTypeEncoder(),
                $qNameFormatter($xsd, 'token') => new StringTypeEncoder(),
                $qNameFormatter($xsd, 'language') => new StringTypeEncoder(),
                $qNameFormatter($xsd, 'NMTOKEN') => new StringTypeEncoder(),
                $qNameFormatter($xsd, 'Name') => new StringTypeEncoder(),
                $qNameFormatter($xsd, 'NCName') => new StringTypeEncoder(),
                $qNameFormatter($xsd, 'ID') => new StringTypeEncoder(),
                $qNameFormatter($xsd, 'IDREF') => new StringTypeEncoder(),
                $qNameFormatter($xsd, 'ENTITY') => new StringTypeEncoder(),

                // Encoded strings
                $qNameFormatter($xsd, 'base64Binary') => new Base64BinaryTypeEncoder(),

                // Bools
                $qNameFormatter($xsd, 'boolean') => new BoolTypeEncoder(),

                // Integers:
                $qNameFormatter($xsd, 'int') => new IntTypeEncoder(),
                $qNameFormatter($xsd, 'long') => new IntTypeEncoder(),
                $qNameFormatter($xsd, 'short') => new IntTypeEncoder(),
                $qNameFormatter($xsd, 'byte') => new IntTypeEncoder(),
                $qNameFormatter($xsd, 'nonPositiveInteger') => new IntTypeEncoder(),
                $qNameFormatter($xsd, 'positiveInteger') => new IntTypeEncoder(),
                $qNameFormatter($xsd, 'nonNegativeInteger') => new IntTypeEncoder(),
                $qNameFormatter($xsd, 'negativeInteger') => new IntTypeEncoder(),
                $qNameFormatter($xsd, 'unsignedLong') => new IntTypeEncoder(),
                $qNameFormatter($xsd, 'unsignedByte') => new IntTypeEncoder(),
                $qNameFormatter($xsd, 'unsignedShort') => new IntTypeEncoder(),
                $qNameFormatter($xsd, 'unsignedInt') => new IntTypeEncoder(),
                $qNameFormatter($xsd, 'integer') => new IntTypeEncoder(),

                // Floats:
                $qNameFormatter($xsd, 'float') => new FloatTypeEncoder(),
                $qNameFormatter($xsd, 'double') => new FloatTypeEncoder(),

                // Scalar:
                $qNameFormatter($xsd, 'anySimpleType') => new ScalarTypeEncoder(),
            ]),
            new MutableMap([])
        );
    }

    /**
     * @param non-empty-string $namespace
     * @param non-empty-string $name
     * @param class-string $class
     * @return $this
     */
    public function addClassMap(string $namespace, string $name, string $class): self
    {
        $this->complextTypeMap->add(
            (new QNameFormatter())($namespace, $name),
            new OptionalElementEncoder(new ObjectEncoder($class))
        );

        return $this;
    }

    /**
     * @param non-empty-string $namespace
     * @param non-empty-string $name
     * @param enum-class $enumClass
     * @return $this
     */
    public function addBackedEnum(string $namespace, string $name, string $enumClass): self
    {
        $this->simpleTypeMap->add(
            (new QNameFormatter())($namespace, $name),
            new ElementEncoder(new SimpleType\BackedEnumTypeEncoder($enumClass))
        );

        return $this;
    }

    /**
     * @param non-empty-string $namespace
     * @param non-empty-string $name
     * @return $this
     */
    public function addSimpleTypeConverter(string $namespace, string $name, XmlEncoder $encoder): self
    {
        $this->simpleTypeMap->add(
            (new QNameFormatter())($namespace, $name),
            $encoder
        );

        return $this;
    }

    /**
     * @param non-empty-string $namespace
     * @param non-empty-string $name
     * @return $this
     */
    public function addComplexTypeConverter(string $namespace, string $name, XmlEncoder $encoder): self
    {
        $this->complextTypeMap->add(
            (new QNameFormatter())($namespace, $name),
            $encoder
        );

        return $this;
    }

    /**
     * @return XmlEncoder<string, mixed>
     */
    public function findSimpleEncoderByXsdType(XsdType $type): XmlEncoder
    {
        return $this->findSimpleEncoderByNamespaceName(
            $type->getXmlNamespace(),
            $type->getXmlTypeName()
        );
    }

    /**
     * @param non-empty-string $namespace
     * @param non-empty-string $name
     * @return XmlEncoder<string, mixed>
     */
    public function findSimpleEncoderByNamespaceName(string $namespace, string $name): XmlEncoder
    {
        $qNameFormatter = new QNameFormatter();

        $found = $this->simpleTypeMap->get($qNameFormatter($namespace, $name));
        if ($found) {
            return $found;
        }

        return new ScalarTypeEncoder();
    }

    public function hasRegisteredSimpleTypeForXsdType(XsdType $type): bool
    {
        $qNameFormatter = new QNameFormatter();

        return $this->simpleTypeMap->contains($qNameFormatter(
            $type->getXmlNamespace(),
            $type->getXmlTypeName()
        ));
    }

    /**
     * @return XmlEncoder<string, mixed>
     */
    public function findComplexEncoderByXsdType(XsdType $type): XmlEncoder
    {
        return $this->findComplexEncoderByNamespaceName(
            $type->getXmlNamespace(),
            $type->getXmlTypeName()
        );
    }

    /**
     * @param non-empty-string $namespace
     * @param non-empty-string $name
     * @return XmlEncoder<string, mixed>
     */
    public function findComplexEncoderByNamespaceName(string $namespace, string $name): XmlEncoder
    {
        $qNameFormatter = new QNameFormatter();

        $found = $this->complextTypeMap->get($qNameFormatter($namespace, $name));
        if ($found) {
            return $found;
        }

        return new OptionalElementEncoder(
            new ObjectEncoder(\stdClass::class)
        );
    }

    public function hasRegisteredComplexTypeForXsdType(XsdType $type): bool
    {
        $qNameFormatter = new QNameFormatter();

        return $this->complextTypeMap->contains($qNameFormatter(
            $type->getXmlNamespace(),
            $type->getXmlTypeName()
        ));
    }

    /**
     * @return XmlEncoder<string, mixed>
     */
    public function detectEncoderForContext(Context $context): XmlEncoder
    {
        return (new EncoderDetector())($context);
    }
}
