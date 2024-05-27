<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Generator;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\TypeInference\XsiTypeEncoder;
use VeeWee\Xml\Writer\Builder\Builder;
use VeeWee\Xml\Xmlns\Xmlns;
use XMLWriter;
use function VeeWee\Xml\Writer\Builder\children;
use function VeeWee\Xml\Writer\Builder\element;
use function VeeWee\Xml\Writer\Builder\namespaced_attribute;


final class EncodedElementBuilder implements Builder
{
    /**
     * @param \Closure(XMLWriter): \Generator<bool> $children
     */
    public function __construct(
        private readonly Context $context,
        private readonly \Closure $children
    ) {
    }

    /**
     * @return Generator<bool>
     */
    public function __invoke(XMLWriter $writer): Generator
    {
        $type = $this->context->type;

        yield from element(
            $type->getXmlTargetNodeName(),
            children([
                namespaced_attribute(
                    Xmlns::xsi()->value(),
                    'xsi',
                    'type',
                    (new XsiTypeEncoder())->iso($this->context)->to($type)
                ),
                $this->children
            ])
        )($writer);
    }
}
