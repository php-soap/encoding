<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml;

use Generator;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\Xml\Xmlns;
use VeeWee\Xml\Writer\Writer;
use XMLWriter;
use function VeeWee\Xml\Writer\Builder\element;
use function VeeWee\Xml\Writer\Builder\namespaced_element;
use function VeeWee\Xml\Writer\Mapper\memory_output;

final class XsdTypeXmlElementBuilder
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
                $this->type->getXmlNamespace() && $this->type->getXmlNamespace() !== Xmlns::xsd()->value()
                    ? namespaced_element(
                        $this->type->getXmlNamespace(),
                        $this->type->getXmlNamespaceName() ?: null,
                        $this->type->getName(),
                        $children
                    )
                    : element($this->type->getName(), $children)
            )
            ->map(memory_output());
    }
}
