<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Generator;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\SimpleType\GuessTypeEncoder;
use VeeWee\Xml\Writer\Builder\Builder;
use XMLWriter;
use function VeeWee\Xml\Writer\Builder\attribute;
use function VeeWee\Xml\Writer\Builder\namespaced_attribute;


final class AttributeBuilder implements Builder
{
    /**
     * @param scalar $value
     */
    public function __construct(
        private readonly Context $context,
        private readonly mixed $value
    ) {
    }

    /**
     * @return Generator<bool>
     */
    public function __invoke(XMLWriter $writer): Generator
    {
        $type = $this->context->type;
        $value = (new GuessTypeEncoder())->iso($this->context)->to($this->value);

        if ($type->getXmlTargetNamespace()) {
            yield from namespaced_attribute(
                $type->getXmlTargetNamespace(),
                $type->getXmlTargetNamespaceName() ?: null,
                $type->getXmlTargetNodeName(),
                $value
            )($writer);
            return;
        }

        yield from attribute(
            $type->getXmlTargetNodeName(),
            $value
        )($writer);
    }
}
