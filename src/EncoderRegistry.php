<?php
declare(strict_types=1);

namespace Soap\Encoding;

use Psl\Collection\MutableMap;
use Soap\Encoding\ClassMap\ClassMapCollection;
use Soap\Encoding\Encoder\AnyElementEncoder;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\ElementEncoder;
use Soap\Encoding\Encoder\EncoderDetector;
use Soap\Encoding\Encoder\ObjectEncoder;
use Soap\Encoding\Encoder\OptionalElementEncoder;
use Soap\Encoding\Encoder\SimpleType;
use Soap\Encoding\Encoder\SoapEnc;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Formatter\QNameFormatter;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\WsdlReader\Metadata\Detector\ApacheMapDetector;
use Soap\WsdlReader\Model\Definitions\EncodingStyle;
use Soap\Xml\Xmlns;
use stdClass;
use Stringable;
use function Psl\Dict\pull;

final class EncoderRegistry
{
    /**
     * @param MutableMap<string, XmlEncoder<mixed, string>> $simpleTypeMap
     * @param MutableMap<string, XmlEncoder<mixed, string>> $complextTypeMap
     */
    private function __construct(
        private MutableMap $simpleTypeMap,
        private MutableMap $complextTypeMap
    ) {
    }

    /**
     * @psalm-suppress InvalidArgument - It is not able to infer the underlying encoder generic types.
     */
    public static function default(): self
    {
        $qNameFormatter = new QNameFormatter();
        $xsd = Xmlns::xsd()->value();
        $xsd1999 = Xmlns::xsd1999()->value();

        return new self(
            /** @var MutableMap<XmlEncoder<mixed, string>> */
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
                $qNameFormatter($xsd, 'NMTOKENS') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'Name') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'NCName') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'NCNames') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'ID') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'IDREF') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'IDREFS') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'ENTITY') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'ENTITIES') => new SimpleType\StringTypeEncoder(),

                // Dates
                $qNameFormatter($xsd, 'date') => SimpleType\DateTypeEncoder::default(),
                $qNameFormatter($xsd, 'dateTime') => SimpleType\DateTimeTypeEncoder::default(),
                // Currently only encoding other date/time-related types as string.
                // It could be possible to use DateTimeInterface or even unix epoch int-s as well in the future.
                $qNameFormatter($xsd, 'time') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'gYear') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'gYearMonth') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'gDay') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'gMonthDay') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'gMonth') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'duration') => new SimpleType\StringTypeEncoder(),

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
                $qNameFormatter($xsd, 'decimal') => new SimpleType\FloatTypeEncoder(),

                // Scalar:
                $qNameFormatter($xsd, 'anyType') => new SimpleType\ScalarTypeEncoder(),
                $qNameFormatter($xsd, 'anyXML') => new SimpleType\ScalarTypeEncoder(),
                $qNameFormatter($xsd, 'anySimpleType') => new SimpleType\ScalarTypeEncoder(),

                // XSD 1999 version
                // @see https://www.w3.org/1999/XMLSchema-datatypes.xsd
                $qNameFormatter($xsd1999, 'string') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd1999, 'boolean') => new SimpleType\BoolTypeEncoder(),
                $qNameFormatter($xsd1999, 'float') => new SimpleType\FloatTypeEncoder(),
                $qNameFormatter($xsd1999, 'double') => new SimpleType\FloatTypeEncoder(),
                $qNameFormatter($xsd1999, 'decimal') => new SimpleType\FloatTypeEncoder(),
                $qNameFormatter($xsd1999, 'timeInstant') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd1999, 'timeDuration') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd1999, 'recurringInstant') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd1999, 'binary') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd1999, 'uriReference') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd1999, 'integer') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd1999, 'nonNegativeInteger') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd1999, 'positiveInteger') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd1999, 'nonPositiveInteger') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd1999, 'negativeInteger') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd1999, 'byte') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd1999, 'int') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd1999, 'long') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd1999, 'short') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd1999, 'unsignedByte') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd1999, 'unsignedInt') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd1999, 'unsignedLong') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd1999, 'unsignedShort') => new SimpleType\IntTypeEncoder(),
                $qNameFormatter($xsd1999, 'date') => SimpleType\DateTypeEncoder::default(),
                $qNameFormatter($xsd1999, 'time') => new SimpleType\StringTypeEncoder(),
            ]),
            /** @var MutableMap<XmlEncoder<mixed, string>> */
            new MutableMap([
                // SOAP 1.1 ENC
                $qNameFormatter(EncodingStyle::SOAP_11->value, 'Array') => new SoapEnc\SoapArrayEncoder(),
                $qNameFormatter(EncodingStyle::SOAP_11->value, 'Struct') => new SoapEnc\SoapObjectEncoder(),

                // SOAP 1.2 ENC
                ...pull(
                    EncodingStyle::listKnownSoap12Version(),
                    static fn () => new SoapEnc\SoapArrayEncoder(),
                    static fn (string $namespace): string => $qNameFormatter($namespace, 'Array')
                ),
                ...pull(
                    EncodingStyle::listKnownSoap12Version(),
                    static fn () => new SoapEnc\SoapObjectEncoder(),
                    static fn (string $namespace): string => $qNameFormatter($namespace, 'Struct')
                ),

                // Apache Map
                $qNameFormatter(ApacheMapDetector::NAMESPACE, 'Map') => new SoapEnc\ApacheMapEncoder(),

                // Special XSD cases
                $qNameFormatter($xsd, 'any') => new AnyElementEncoder(),
            ])
        );
    }

    /**
     * @param non-empty-string $namespace
     * @param non-empty-string $name
     * @param class-string $class
     */
    public function addClassMap(string $namespace, string $name, string $class): self
    {
        $this->complextTypeMap->add(
            (new QNameFormatter())($namespace, $name),
            new OptionalElementEncoder(new ObjectEncoder($class))
        );

        return $this;
    }

    public function addClassMapCollection(ClassMapCollection $classMapCollection): self
    {
        foreach ($classMapCollection as $classMap) {
            $this->addClassMap(
                $classMap->getXmlNamespace(),
                $classMap->getXmlType(),
                $classMap->getPhpClassName()
            );
        }

        return $this;
    }

    /**
     * @template T of \BackedEnum
     *
     * @param non-empty-string $namespace
     * @param non-empty-string $name
     * @param enum-string<T> $enumClass
     */
    public function addBackedEnum(string $namespace, string $name, string $enumClass): self
    {
        $this->simpleTypeMap->add(
            (new QNameFormatter())($namespace, $name),
            new ElementEncoder(new SimpleType\BackedEnumTypeEncoder($enumClass))
        );

        return $this;
    }

    public function addBackedEnumClassMapCollection(ClassMapCollection $classMapCollection): self
    {
        foreach ($classMapCollection as $classMap) {
            $this->addBackedEnum(
                $classMap->getXmlNamespace(),
                $classMap->getXmlType(),
                $classMap->getPhpClassName()
            );
        }

        return $this;
    }

    /**
     * @param non-empty-string $namespace
     * @param non-empty-string $name
     * @param XmlEncoder<mixed, string> $encoder
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
     * @param XmlEncoder<mixed, string> $encoder
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
     * @return XmlEncoder<mixed, string>
     */
    public function findSimpleEncoderByXsdType(XsdType $type): XmlEncoder
    {
        return $this->findSimpleEncoderByNamespaceName(
            $type->getXmlNamespace(),
            $type->getXmlTypeName()
        );
    }

    /**
     * @return XmlEncoder<mixed, string>
     */
    public function findSimpleEncoderByNamespaceName(string $namespace, string $name): XmlEncoder
    {
        $qNameFormatter = new QNameFormatter();

        $found = $this->simpleTypeMap->get($qNameFormatter($namespace, $name));
        if ($found) {
            return $found;
        }

        return SimpleType\ScalarTypeEncoder::default();
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
     * @return XmlEncoder<mixed, string>
     */
    public function findComplexEncoderByXsdType(XsdType $type): XmlEncoder
    {
        return $this->findComplexEncoderByNamespaceName(
            $type->getXmlNamespace(),
            $type->getXmlTypeName()
        );
    }

    /**
     * @return XmlEncoder<mixed, string>
     */
    public function findComplexEncoderByNamespaceName(string $namespace, string $name): XmlEncoder
    {
        $qNameFormatter = new QNameFormatter();

        $found = $this->complextTypeMap->get($qNameFormatter($namespace, $name));
        if ($found) {
            return $found;
        }

        return new OptionalElementEncoder(
            new ObjectEncoder(stdClass::class)
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
     * @return XmlEncoder<mixed, string|Stringable>
     */
    public function detectEncoderForContext(Context $context): XmlEncoder
    {
        return EncoderDetector::default()($context);
    }
}
