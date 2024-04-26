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
                $qNameFormatter($xsd, 'string') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'anyURI') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'qname') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'NOTATION') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'normalizedString') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'token') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'language') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'NMTOKEN') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'Name') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'NCName') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'ID') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'IDREF') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'ENTITY') => new SimpleType\StringTypeEncoder(),

                // Encoded strings
                $qNameFormatter($xsd, 'base64Binary') => new SimpleType\Base64BinaryTypeEncoder(),
                $qNameFormatter($xsd, 'hexBinary') => new SimpleType\HexBinaryTypeEncoder(),

                // Bools
                $qNameFormatter($xsd, 'boolean') => new SimpleType\BoolTypeEncoder(),

                // Integers:
                $qNameFormatter($xsd, 'int') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd, 'long') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd, 'short') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd, 'byte') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd, 'nonPositiveInteger') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd, 'positiveInteger') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd, 'nonNegativeInteger') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd, 'negativeInteger') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd, 'unsignedLong') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd, 'unsignedByte') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd, 'unsignedShort') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd, 'unsignedInt') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd, 'integer') => new SimpleType\IntTypeEncoder(),

                // Floats:
                $qNameFormatter($xsd, 'float') => new SimpleType\FloatTypeEncoder(),
                $qNameFormatter($xsd, 'double') => new SimpleType\FloatTypeEncoder(),

                // Scalar:
                $qNameFormatter($xsd, 'anySimpleType') => new SimpleType\ScalarTypeEncoder(),
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
