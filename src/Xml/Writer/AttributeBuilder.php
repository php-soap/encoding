<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Generator;
use Soap\Encoding\Encoder\Context;
use Soap\Engine\Metadata\Model\XsdType;
use VeeWee\Xml\Writer\Builder\Builder;
use XMLWriter;
use function VeeWee\Xml\Writer\Builder\attribute;
use function VeeWee\Xml\Writer\Builder\namespaced_attribute;

final class AttributeBuilder implements Builder
{
    public function __construct(
        private readonly Context $context,
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

        if ($qualified && $this->type->getXmlTargetNamespace()) {
            yield from namespaced_attribute(
                $this->type->getXmlTargetNamespace(),
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
