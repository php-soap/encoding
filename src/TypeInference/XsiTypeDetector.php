<?php
declare(strict_types=1);

namespace Soap\Encoding\TypeInference;

use Psl\Option\Option;
use Soap\Encoding\Encoder\Context;
use Soap\Xml\Xmlns as SoapXmlns;
use VeeWee\Xml\Dom\Document;
use VeeWee\Xml\Xmlns\Xmlns as XmlXmlns;
use function Psl\Option\none;
use function Psl\Option\some;

final class XsiTypeDetector
{
    public static function detectFromValue(Context $context, mixed $value): string
    {
        $xsd = $context->namespaces->lookupNameFromNamespace(SoapXmlns::xsd()->value())->unwrap();

        return self::detectFromContext($context)->unwrapOrElse(
            static fn() => match(true) {
                is_string($value) => $xsd . ':string',
                is_int($value) => $xsd . ':int',
                is_float($value) => $xsd . ':float',
                is_bool($value) => $xsd . ':boolean',
                default => $xsd . ':anyType',
            }
        );
    }

    public static function detectFromXmlElement(Context $context, string $xmlElement): string
    {
        return self::detectFromContext($context)->unwrapOrElse(
            static function() use ($context, $xmlElement): string {
                $element = Document::fromXmlString($xmlElement)->locateDocumentElement();
                $xsd = $context->namespaces->lookupNameFromNamespace(SoapXmlns::xsd()->value())->unwrap();

                return $element->getAttributeNS(XmlXmlns::xsi()->value(), 'type') ?: $xsd . ':anyType';
            }
        );
    }

    /**
     * @param Context $context
     * @return Option<string>
     */
    private static function detectFromContext(Context $context): Option
    {
        $type = $context->type;

        if ($type->getBaseType() === 'mixed') {
            return none();
        }

        return some(
            sprintf(
                '%s:%s',
                $context->namespaces->lookupNameFromNamespace($type->getXmlNamespace())->unwrap(),
                $type->getName()
            )
        );
    }
}
