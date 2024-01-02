<?php
declare(strict_types=1);

namespace Soap\Encoding;

use Psl\Collection\Map;
use Psl\Collection\MutableMap;
use Soap\Encoding\Encoder\Base64BinaryEncoder;
use Soap\Encoding\Encoder\BoolEncoder;
use Soap\Encoding\Encoder\FloatEncoder;
use Soap\Encoding\Encoder\GuessEncoder;
use Soap\Encoding\Encoder\IntEncoder;
use Soap\Encoding\Encoder\ObjectEncoder;
use Soap\Encoding\Encoder\ScalarEncoder;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Encoder\StringEncoder;
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
            $qNameFormatter($xsd, 'string') => new StringEncoder(),
            $qNameFormatter($xsd, 'anyURI') => new StringEncoder(),
            $qNameFormatter($xsd, 'qname') => new StringEncoder(),
            $qNameFormatter($xsd, 'NOTATION') => new StringEncoder(),
            $qNameFormatter($xsd, 'normalizedString') => new StringEncoder(),
            $qNameFormatter($xsd, 'token') => new StringEncoder(),
            $qNameFormatter($xsd, 'language') => new StringEncoder(),
            $qNameFormatter($xsd, 'NMTOKEN') => new StringEncoder(),
            $qNameFormatter($xsd, 'Name') => new StringEncoder(),
            $qNameFormatter($xsd, 'NCName') => new StringEncoder(),
            $qNameFormatter($xsd, 'ID') => new StringEncoder(),
            $qNameFormatter($xsd, 'IDREF') => new StringEncoder(),
            $qNameFormatter($xsd, 'ENTITY') => new StringEncoder(),

            // Encoded strings
            $qNameFormatter($xsd, 'base64Binary') => new Base64BinaryEncoder(),

            // Bools
            $qNameFormatter($xsd, 'boolean') => new BoolEncoder(),

            // Integers:
            $qNameFormatter($xsd, 'int') => new IntEncoder(),
            $qNameFormatter($xsd, 'long') => new IntEncoder(),
            $qNameFormatter($xsd, 'short') => new IntEncoder(),
            $qNameFormatter($xsd, 'byte') => new IntEncoder(),
            $qNameFormatter($xsd, 'nonPositiveInteger') => new IntEncoder(),
            $qNameFormatter($xsd, 'positiveInteger') => new IntEncoder(),
            $qNameFormatter($xsd, 'nonNegativeInteger') => new IntEncoder(),
            $qNameFormatter($xsd, 'negativeInteger') => new IntEncoder(),
            $qNameFormatter($xsd, 'unsignedLong') => new IntEncoder(),
            $qNameFormatter($xsd, 'unsignedByte') => new IntEncoder(),
            $qNameFormatter($xsd, 'unsignedShort') => new IntEncoder(),
            $qNameFormatter($xsd, 'unsignedInt') => new IntEncoder(),
            $qNameFormatter($xsd, 'unsignedLong') => new IntEncoder(),
            $qNameFormatter($xsd, 'integer') => new IntEncoder(),

            // Floats:
            $qNameFormatter($xsd, 'float') => new FloatEncoder(),
            $qNameFormatter($xsd, 'double') => new FloatEncoder(),

            // Scalar:
            $qNameFormatter($xsd, 'anySimpleType') => new ScalarEncoder(),
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
