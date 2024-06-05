<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Generator;
use Soap\Encoding\Encoder\Context;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Parser\Xml\QnameParser;
use VeeWee\Xml\Xmlns\Xmlns;
use XMLWriter;
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
