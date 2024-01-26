<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Generator;
use Soap\Engine\Metadata\Model\XsdType;
use VeeWee\Xml\Writer\Builder\Builder;
use XMLWriter;
use function VeeWee\Xml\Writer\Builder\element;
use function VeeWee\Xml\Writer\Builder\namespaced_element;


final class ElementBuilder implements Builder
{
    /**
     * @param XsdType $type
     * @param \Closure(XMLWriter): \Generator<bool> $children
     */
    public function __construct(
        private readonly XsdType $type,
        private readonly \Closure $children
    ) {
    }

    /**
     * @return Generator<bool>
     */
    public function __invoke(XMLWriter $writer): Generator
    {
        if ($this->type->getXmlTargetNamespace()) {
            yield from namespaced_element(
                $this->type->getXmlTargetNamespace(),
                $this->type->getXmlTargetNamespaceName() ?: null,
                $this->type->getXmlTargetNodeName(),
                $this->children
            )($writer);
            return;
        }

        yield from element(
            $this->type->getXmlTargetNodeName(),
            $this->children
        )($writer);
    }
}
