<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Formatter\QNameFormatter;
use Soap\Xml\Xmlns;
use VeeWee\Reflecta\Iso\Iso;

/**
 * @implements XmlEncoder<string, mixed>
 */
final class GuessTypeEncoder implements XmlEncoder
{
    public function iso(Context $context): Iso
    {
        return $this->detectIsoFromType($context)->iso($context);
    }

    /**
     * @return XmlEncoder<string, mixed>
     */
    private function detectIsoFromType(Context $context): XmlEncoder
    {
        $xsdType = $context->type;

        $qNameFormatter = new QNameFormatter();
        $qname = $qNameFormatter($xsdType->getXmlNamespace(), $xsdType->getBaseTypeOrFallbackToName());
        $xsd = Xmlns::xsd()->value();

        // TODO => Move to lookup Map instead of match.

        return match($qname) {
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
            default => new ScalarTypeEncoder(),
        };
    }
}
