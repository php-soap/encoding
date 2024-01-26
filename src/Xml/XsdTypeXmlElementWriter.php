<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml;

use Generator;
use Soap\Engine\Metadata\Model\XsdType;
use VeeWee\Xml\Writer\Writer;
use XMLWriter;
use function VeeWee\Xml\Writer\Builder\element;
use function VeeWee\Xml\Writer\Builder\namespaced_element;
use function VeeWee\Xml\Writer\Mapper\memory_output;

final class XsdTypeXmlElementWriter
{
    public function __construct(
        private readonly XsdType $type
    ) {
    }

    /**
     * @param callable(XMLWriter): Generator<bool> $children
     */
    public function __invoke(callable $children): string
    {
        return Writer::inMemory()
            ->write(
                $this->type->getXmlTargetNamespace()
                    ? namespaced_element(
                        $this->type->getXmlTargetNamespace(),
                        $this->type->getXmlTargetNamespaceName() ?: null,
                        $this->type->getXmlTargetNodeName(),
                        $children
                    )
                    : element($this->type->getXmlTargetNodeName(), $children)
            )
            ->map(memory_output());
    }
}
