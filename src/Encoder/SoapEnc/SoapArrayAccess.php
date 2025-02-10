<?php declare(strict_types=1);

namespace Soap\Encoding\Encoder\SoapEnc;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Engine\Metadata\Model\TypeMeta;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Parser\Xml\QnameParser;
use Soap\Xml\Xmlns;
use Stringable;
use function Psl\Result\try_catch;

final class SoapArrayAccess
{
    /**
     * @param XmlEncoder<mixed, Stringable|string> $itemEncoder
     */
    public function __construct(
        public readonly string $xsiType,
        public readonly Context $itemContext,
        public readonly XmlEncoder $itemEncoder,
    ) {
    }

    public static function forContext(Context $context): self
    {
        $meta = $context->type->getMeta();
        [$namespace, $nodePrefix, $nodeType] = $meta->arrayType()
            ->map(static fn (array $info): array => [$info['namespace'], ...(new QnameParser())($info['itemType'])])
            ->unwrapOr([
                Xmlns::xsd()->value(),
                $context->namespaces->lookupNameFromNamespace(Xmlns::xsd()->value())->unwrapOr('xsd'),
                'anyType'
            ]);
        $itemNodeName = $meta->arrayNodeName()->unwrapOr(null);
        $xsiType = ltrim($nodePrefix . ':' . $nodeType, ':');

        $xsdType = try_catch(
            static fn (): XsdType => $context->metadata->getTypes()
                ->fetchByNameAndXmlNamespace($nodeType, $namespace)
                ->getXsdType(),
            static fn (): XsdType => XsdType::any()
                ->copy($nodeType)
                ->withXmlTypeName($nodeType)
                ->withXmlNamespace($namespace)
                ->withMeta(static fn (TypeMeta $meta): TypeMeta => $meta->withIsElement(true))
        );

        if ($context->bindingUse === BindingUse::ENCODED || $itemNodeName !== null) {
            $xsdType = $xsdType->withXmlTargetNodeName($itemNodeName ?? 'item');
        } else {
            $xsdType = $xsdType
                ->withXmlTargetNodeName($nodeType)
                ->withXmlTargetNamespaceName($nodePrefix)
                ->withXmlTargetNamespace($namespace)
                ->withMeta(
                    static fn (TypeMeta $meta): TypeMeta => $meta->withIsQualified(true),
                );
        }

        $itemContext = $context->withType($xsdType);
        $encoder = $context->registry->detectEncoderForContext($itemContext);

        return new self(
            $xsiType,
            $itemContext,
            $encoder,
        );
    }
}
