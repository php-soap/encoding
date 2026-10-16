<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Generator;
use Soap\Engine\Metadata\Model\XsdType;
use VeeWee\Xml\Writer\Builder\Builder;
use VeeWee\Xml\Xmlns\Xmlns;
use XMLWriter;
use function VeeWee\Xml\Writer\Builder\attribute;
use function VeeWee\Xml\Writer\Builder\namespaced_attribute;
use function VeeWee\Xml\Writer\Builder\prefixed_attribute;

final class AttributeBuilder implements Builder
{
    public function __construct(
        private readonly XsdType $type,
        private readonly string $value
    ) {
    }

    /**
     * @return Generator<bool>
     */
    public function __invoke(XMLWriter $writer): Generator
    {
        $qualified = $this->type->getMeta()->isQualified()->unwrapOr(false);
        $namespace = $this->type->getXmlTargetNamespace();

        // The xml prefix is bound by the XML spec. Passing its namespace to XMLWriter would declare it again.
        if ($qualified && $namespace === Xmlns::xml()->value()) {
            yield from prefixed_attribute('xml', $this->type->getXmlTargetNodeName(), $this->value)($writer);
            return;
        }

        if ($qualified && $namespace) {
            yield from namespaced_attribute(
                $namespace,
                $this->type->getXmlTargetNamespaceName() ?: null,
                $this->type->getXmlTargetNodeName(),
                $this->value
            )($writer);
            return;
        }

        yield from attribute(
            $this->type->getXmlTargetNodeName(),
            $this->value
        )($writer);
    }
}
