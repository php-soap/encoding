<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Closure;
use Generator;
use Soap\Encoding\Encoder\Context;
use VeeWee\Xml\Writer\Builder\Builder;
use XMLWriter;
use function VeeWee\Xml\Writer\Builder\element;
use function VeeWee\Xml\Writer\Builder\namespaced_element;

final class ElementBuilder implements Builder
{
    /**
     * @param Closure(XMLWriter): Generator<bool> $children
     */
    public function __construct(
        private readonly Context $context,
        private readonly Closure $children
    ) {
    }

    /**
     * @return Generator<bool>
     */
    public function __invoke(XMLWriter $writer): Generator
    {
        $type = $this->context->type;
        $meta = $type->getMeta();
        $qualified = $meta->isQualified()->unwrapOr(false);

        if ($qualified && $type->getXmlTargetNamespace()) {
            yield from namespaced_element(
                $type->getXmlTargetNamespace(),
                $type->getXmlTargetNamespaceName() ?: null,
                $type->getXmlTargetNodeName(),
                $this->children
            )($writer);
            return;
        }

        yield from element(
            $type->getXmlTargetNodeName(),
            $this->children
        )($writer);
    }
}
