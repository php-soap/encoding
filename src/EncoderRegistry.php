<?php
declare(strict_types=1);

namespace Soap\Encoding;

use Psl\Collection\Map;
use Psl\Collection\MutableMap;
use Soap\Encoding\Encoder\ElementEncoder;
use Soap\Encoding\Encoder\GuessEncoder;
use Soap\Encoding\Encoder\SimpleType;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Formatter\QNameFormatter;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\Xml\Xmlns;

final class EncoderRegistry
{
    /**
     * @param MutableMap<string, XmlEncoder> $registry
     */
    private function __construct(
        private MutableMap $registry
    ) {
    }

    public static function default(): self
    {
        $qNameFormatter = new QNameFormatter();
        $xsd = Xmlns::xsd()->value();
        $xsd1999 = '.????';

        return new self(new MutableMap([
            // Strings:
            $qNameFormatter($xsd, 'string') => new ElementEncoder(new SimpleType\StringTypeEncoder()),
            $qNameFormatter($xsd, 'anyURI') => new ElementEncoder(new SimpleType\StringTypeEncoder()),
            $qNameFormatter($xsd, 'qname') => new ElementEncoder(new SimpleType\StringTypeEncoder()),
            $qNameFormatter($xsd, 'NOTATION') => new ElementEncoder(new SimpleType\StringTypeEncoder()),
            $qNameFormatter($xsd, 'normalizedString') => new ElementEncoder(new SimpleType\StringTypeEncoder()),
            $qNameFormatter($xsd, 'token') => new ElementEncoder(new SimpleType\StringTypeEncoder()),
            $qNameFormatter($xsd, 'language') => new ElementEncoder(new SimpleType\StringTypeEncoder()),
            $qNameFormatter($xsd, 'NMTOKEN') => new ElementEncoder(new SimpleType\StringTypeEncoder()),
            $qNameFormatter($xsd, 'Name') => new ElementEncoder(new SimpleType\StringTypeEncoder()),
            $qNameFormatter($xsd, 'NCName') => new ElementEncoder(new SimpleType\StringTypeEncoder()),
            $qNameFormatter($xsd, 'ID') => new ElementEncoder(new SimpleType\StringTypeEncoder()),
            $qNameFormatter($xsd, 'IDREF') => new ElementEncoder(new SimpleType\StringTypeEncoder()),
            $qNameFormatter($xsd, 'ENTITY') => new ElementEncoder(new SimpleType\StringTypeEncoder()),

            // Encoded strings
            $qNameFormatter($xsd, 'base64Binary') => new ElementEncoder(new SimpleType\Base64BinaryTypeEncoder()),

            // Bools
            $qNameFormatter($xsd, 'boolean') => new ElementEncoder(new SimpleType\BoolTypeEncoder()),

            // Integers:
            $qNameFormatter($xsd, 'int') => new ElementEncoder(new SimpleType\IntTypeEncoder()),
            $qNameFormatter($xsd, 'long') => new ElementEncoder(new SimpleType\IntTypeEncoder()),
            $qNameFormatter($xsd, 'short') => new ElementEncoder(new SimpleType\IntTypeEncoder()),
            $qNameFormatter($xsd, 'byte') => new ElementEncoder(new SimpleType\IntTypeEncoder()),
            $qNameFormatter($xsd, 'nonPositiveInteger') => new ElementEncoder(new SimpleType\IntTypeEncoder()),
            $qNameFormatter($xsd, 'positiveInteger') => new ElementEncoder(new SimpleType\IntTypeEncoder()),
            $qNameFormatter($xsd, 'nonNegativeInteger') => new ElementEncoder(new SimpleType\IntTypeEncoder()),
            $qNameFormatter($xsd, 'negativeInteger') => new ElementEncoder(new SimpleType\IntTypeEncoder()),
            $qNameFormatter($xsd, 'unsignedLong') => new ElementEncoder(new SimpleType\IntTypeEncoder()),
            $qNameFormatter($xsd, 'unsignedByte') => new ElementEncoder(new SimpleType\IntTypeEncoder()),
            $qNameFormatter($xsd, 'unsignedShort') => new ElementEncoder(new SimpleType\IntTypeEncoder()),
            $qNameFormatter($xsd, 'unsignedInt') => new ElementEncoder(new SimpleType\IntTypeEncoder()),
            $qNameFormatter($xsd, 'unsignedLong') => new ElementEncoder(new SimpleType\IntTypeEncoder()),
            $qNameFormatter($xsd, 'integer') => new ElementEncoder(new SimpleType\IntTypeEncoder()),

            // Floats:
            $qNameFormatter($xsd, 'float') => new ElementEncoder(new SimpleType\IntTypeEncoder()),
            $qNameFormatter($xsd, 'double') => new ElementEncoder(new SimpleType\IntTypeEncoder()),

            // Scalar:
            $qNameFormatter($xsd, 'anySimpleType') => new ElementEncoder(new SimpleType\ScalarTypeEncoder()),
        ]));
    }

    /**
     * @param non-empty-string $namespace
     * @param non-empty-string $name
     * @param class-string $class
     * @return $this
     */
    public function addClassMap(string $namespace, string $name, string $class): self
    {
        $this->registry->add(
            (new QNameFormatter())($namespace, $name),
            new ObjectEncoder($class)
        );

        return $this;
    }

    /**
     * @param non-empty-string $namespace
     * @param non-empty-string $name
     * @return $this
     */
    public function addTypeConverter(string $namespace, string $name, XmlEncoder $encoder): self
    {
        $this->registry->add(
            (new QNameFormatter())($namespace, $name),
            $encoder
        );

        return $this;
    }

    /**
     * @return XmlEncoder<string, mixed>
     */
    public function findByXsdType(XsdType $type): XmlEncoder
    {
        return $this->findByNamespaceName($type->getXmlNamespace(), $type->getBaseTypeOrFallbackToName());
    }

    /**
     * @param non-empty-string $namespace
     * @param non-empty-string $name
     * @return XmlEncoder<string, mixed>
     */
    public function findByNamespaceName(string $namespace, string $name): XmlEncoder
    {
        $qNameFormatter = new QNameFormatter();

        $found = $this->registry->get($qNameFormatter($namespace, $name));
        if ($found) {
            return $found;
        }

        return new GuessEncoder();
    }
}
