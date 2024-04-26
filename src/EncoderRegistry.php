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
                $qNameFormatter($xsd, 'NMTOKENS') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'Name') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'NCName') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'ID') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'IDREF') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'IDREFS') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'ENTITY') => new SimpleType\StringTypeEncoder(),
                $qNameFormatter($xsd, 'ENTITIES') => new SimpleType\StringTypeEncoder(),

                // Dates
                /*
                 * 	{{XSD_DATETIME, XSD_DATETIME_STRING, XSD_NAMESPACE, NULL, NULL, NULL}, to_zval_stringc, to_xml_datetime},
                    {{XSD_TIME, XSD_TIME_STRING, XSD_NAMESPACE, NULL, NULL, NULL}, to_zval_stringc, to_xml_time},
                    {{XSD_DATE, XSD_DATE_STRING, XSD_NAMESPACE, NULL, NULL, NULL}, to_zval_stringc, to_xml_date},
                    {{XSD_GYEARMONTH, XSD_GYEARMONTH_STRING, XSD_NAMESPACE, NULL, NULL, NULL}, to_zval_stringc, to_xml_gyearmonth},
                    {{XSD_GYEAR, XSD_GYEAR_STRING, XSD_NAMESPACE, NULL, NULL, NULL}, to_zval_stringc, to_xml_gyear},
                    {{XSD_GMONTHDAY, XSD_GMONTHDAY_STRING, XSD_NAMESPACE, NULL, NULL, NULL}, to_zval_stringc, to_xml_gmonthday},
                    {{XSD_GDAY, XSD_GDAY_STRING, XSD_NAMESPACE, NULL, NULL, NULL}, to_zval_stringc, to_xml_gday},
                    {{XSD_GMONTH, XSD_GMONTH_STRING, XSD_NAMESPACE, NULL, NULL, NULL}, to_zval_stringc, to_xml_gmonth},
                    {{XSD_DURATION, XSD_DURATION_STRING, XSD_NAMESPACE, NULL, NULL, NULL}, to_zval_stringc, to_xml_duration},
                 */

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
                $qNameFormatter($xsd, 'any') => new SimpleType\ScalarTypeEncoder(),
                $qNameFormatter($xsd, 'anyType') => new SimpleType\ScalarTypeEncoder(),
                $qNameFormatter($xsd, 'anyXML') => new SimpleType\ScalarTypeEncoder(),
                $qNameFormatter($xsd, 'anySimpleType') => new SimpleType\ScalarTypeEncoder(),


                // 19999
                /* support some of the 1999 data types */
                // {{XSD_STRING, XSD_STRING_STRING, XSD_1999_NAMESPACE, NULL, NULL, NULL}, to_zval_string, to_xml_string},
                // {{XSD_BOOLEAN, XSD_BOOLEAN_STRING, XSD_1999_NAMESPACE, NULL, NULL, NULL}, to_zval_bool, to_xml_bool},
                // {{XSD_DECIMAL, XSD_DECIMAL_STRING, XSD_1999_NAMESPACE, NULL, NULL, NULL}, to_zval_stringc, to_xml_string},
                // {{XSD_FLOAT, XSD_FLOAT_STRING, XSD_1999_NAMESPACE, NULL, NULL, NULL}, to_zval_double, to_xml_double},
                // {{XSD_DOUBLE, XSD_DOUBLE_STRING, XSD_1999_NAMESPACE, NULL, NULL, NULL}, to_zval_double, to_xml_double},

                // {{XSD_LONG, XSD_LONG_STRING, XSD_1999_NAMESPACE, NULL, NULL, NULL}, to_zval_long, to_xml_long},
                // {{XSD_INT, XSD_INT_STRING, XSD_1999_NAMESPACE, NULL, NULL, NULL}, to_zval_long, to_xml_long},
                // {{XSD_SHORT, XSD_SHORT_STRING, XSD_1999_NAMESPACE, NULL, NULL, NULL}, to_zval_long, to_xml_long},
                // {{XSD_BYTE, XSD_BYTE_STRING, XSD_1999_NAMESPACE, NULL, NULL, NULL}, to_zval_long, to_xml_long},
                // {{XSD_1999_TIMEINSTANT, XSD_1999_TIMEINSTANT_STRING, XSD_1999_NAMESPACE, NULL, NULL, NULL}, to_zval_stringc, to_xml_string},

            ]),
            new MutableMap([
                // TODO
                // {{APACHE_MAP, APACHE_MAP_STRING, APACHE_NAMESPACE, NULL, NULL, NULL}, to_zval_map, to_xml_map},
                // {{SOAP_ENC_OBJECT, SOAP_ENC_OBJECT_STRING, SOAP_1_1_ENC_NAMESPACE, NULL, NULL, NULL}, to_zval_object, to_xml_object},
                // {{SOAP_ENC_ARRAY, SOAP_ENC_ARRAY_STRING, SOAP_1_1_ENC_NAMESPACE, NULL, NULL, NULL}, to_zval_array, to_xml_array},
                // {{SOAP_ENC_OBJECT, SOAP_ENC_OBJECT_STRING, SOAP_1_2_ENC_NAMESPACE, NULL, NULL, NULL}, to_zval_object, to_xml_object},
                // {{SOAP_ENC_ARRAY, SOAP_ENC_ARRAY_STRING, SOAP_1_2_ENC_NAMESPACE, NULL, NULL, NULL}, to_zval_array, to_xml_array},
            ])
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
