<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Closure;
use Generator;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\Feature;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\TypeInference\XsiTypeDetector;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Parser\Xml\QnameParser;
use VeeWee\Xml\Xmlns\Xmlns;
use XMLWriter;
use function VeeWee\Xml\Writer\Builder\children;
use function VeeWee\Xml\Writer\Builder\namespace_attribute;
use function VeeWee\Xml\Writer\Builder\namespaced_attribute;

final class XsiAttributeBuilder
{
    public function __construct(
        private readonly Context $context,
        private readonly string $xsiType,
        private readonly bool $includeXsiTargetNamespace = true
    ) {
    }

    /**
     * @return Closure(XMLWriter): Generator<bool>
     */
    public static function forEncodedValue(
        Context $context,
        XmlEncoder $encoder,
        mixed $value,
        ?bool $forceIncludeXsiTargetNamespace = null,
    ): Closure {
        if ($context->bindingUse !== BindingUse::ENCODED) {
            return children([]);
        }

        [$xsiType, $includeXsiTargetNamespace] = match(true) {
            $encoder instanceof Feature\XsiTypeCalculator => [
                $encoder->resolveXsiTypeForValue($context, $value),
                $forceIncludeXsiTargetNamespace ?? $encoder->shouldIncludeXsiTargetNamespace($context),
            ],
            default => [
                self::resolveXsiTypeForValue($context, $value),
                $forceIncludeXsiTargetNamespace ?? self::shouldIncludeXsiTargetNamespace($context),
            ],
        };

        return (new self(
            $context,
            $xsiType,
            $includeXsiTargetNamespace,
        ))(...);
    }

    /**
     * Can be used as a default fallback function when implementing the XsiTypeCalculator interface.
     * Tells the XsiAttributeBuilder what xsi:type attribute should be set to for a given value.
     */
    public static function resolveXsiTypeForValue(Context $context, mixed $value): string
    {
        return XsiTypeDetector::detectFromValue($context, $value);
    }

    /**
     * Can be used as a default fallback function when implementing the XsiTypeCalculator interface.
     * Tells the XsiAttributeBuilder that the prefix of the xsi:type should be imported as a xmlns namespace.
     */
    public static function shouldIncludeXsiTargetNamespace(Context $context): bool
    {
        $type = $context->type;

        return $type->getXmlTargetNamespace() !== $type->getXmlNamespace()
            || !$type->getMeta()->isQualified()->unwrapOr(false);
    }

    /**
     * @return Generator<bool>
     */
    public function __invoke(XMLWriter $writer): Generator
    {
        if ($this->context->bindingUse !== BindingUse::ENCODED) {
            return;
        }

        // Add xmlns for target namespace
        [$prefix] = (new QnameParser())($this->xsiType);
        if ($prefix && $this->includeXsiTargetNamespace) {
            yield from namespace_attribute(
                $this->context->namespaces->lookupNamespaceFromName($prefix)->unwrap(),
                $prefix
            )($writer);
        }

        yield from namespaced_attribute(
            Xmlns::xsi()->value(),
            'xsi',
            'type',
            $this->xsiType
        )($writer);
    }
}
