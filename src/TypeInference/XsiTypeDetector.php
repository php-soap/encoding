<?php
declare(strict_types=1);

namespace Soap\Encoding\TypeInference;

use Psl\Option\Option;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Parser\Xml\QnameParser;
use Soap\Xml\Xmlns as SoapXmlns;
use VeeWee\Xml\Xmlns\Xmlns;
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

    /**
     * @param Context $context
     * @param \DOMElement $element
     * @return Option<XmlEncoder<string, mixed>>
     */
    public static function detectEncoderFromXmlElement(Context $context, \DOMElement $element): Option
    {
        if ($context->bindingUse !== BindingUse::ENCODED) {
            return none();
        }

        $xsiType = $element->getAttributeNS(Xmlns::xsi()->value(), 'type');
        if (!$xsiType) {
            return none();
        }

        [$prefix, $localName] = (new QnameParser)($xsiType);
        if (!$prefix) {
            return none();
        }

        $namespace = $context->namespaces->lookupNamespaceFromName($prefix);
        if (!$namespace->isSome()) {
            return none();
        }

        $namespaceUri = $namespace->unwrap();
        $type = $context->type;
        $meta = $type->getMeta();

        return some(
            match(true) {
                $meta->isSimple()->unwrapOr(false) => $context->registry->findSimpleEncoderByNamespaceName($namespaceUri, $localName),
                default => $context->registry->findComplexEncoderByNamespaceName($namespaceUri, $localName),
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
        $isMixed = $type->getBaseType() === 'mixed';
        $isUnion = $type->getMeta()->unions()->isSome();

        if ($isMixed && !$isUnion) {
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
