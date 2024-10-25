<?php
declare(strict_types=1);

namespace Soap\Encoding\TypeInference;

use DOMElement;
use Psl\Option\Option;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Parser\Xml\QnameParser;
use Soap\Xml\Xmlns as SoapXmlns;
use VeeWee\Xml\Xmlns\Xmlns;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function Psl\Option\none;
use function Psl\Option\some;
use function sprintf;

final class XsiTypeDetector
{
    /**
     * @psalm-param mixed $value
     */
    public static function detectFromValue(Context $context, mixed $value): string
    {
        return self::detectFromContext($context)->unwrapOrElse(
            static function () use ($context, $value) {
                $xsd = $context->namespaces->lookupNameFromNamespace(SoapXmlns::xsd()->value())->unwrap();

                return match (true) {
                    is_string($value) => $xsd . ':string',
                    is_int($value) => $xsd . ':int',
                    is_float($value) => $xsd . ':float',
                    is_bool($value) => $xsd . ':boolean',
                    default => $xsd . ':anyType',
                };
            }
        );
    }

    /**
     * @return Option<XmlEncoder<mixed, string>>
     */
    public static function detectEncoderFromXmlElement(Context $context, DOMElement $element): Option
    {
        if ($context->bindingUse !== BindingUse::ENCODED) {
            return none();
        }

        $xsiType = $element->getAttributeNS(Xmlns::xsi()->value(), 'type');
        if (!$xsiType) {
            return none();
        }

        [$prefix, $localName] = (new QnameParser)($xsiType);
        if (!$prefix || !$localName) {
            return none();
        }

        $namespace = $context->namespaces->lookupNamespaceFromName($prefix);
        if (!$namespace->isSome()) {
            return none();
        }

        $namespaceUri = $namespace->unwrap();
        if (!$namespaceUri) {
            return none();
        }

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
     * @return Option<non-empty-string>
     */
    private static function detectFromContext(Context $context): Option
    {
        $type = $context->type;
        $isAny = $type->getXmlNamespace() === SoapXmlns::xsd()->value() && $type->getName() === 'anyType';
        $isUnion = $type->getMeta()->unions()->isSome();

        if ($isAny && !$isUnion) {
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
