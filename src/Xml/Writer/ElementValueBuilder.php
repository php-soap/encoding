<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Generator;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\TypeInference\XsiTypeDetector;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use VeeWee\Reflecta\Iso\Iso;
use XMLWriter;
use function VeeWee\Xml\Writer\Builder\children;
use function VeeWee\Xml\Writer\Builder\value;

final class ElementValueBuilder
{
    /**
     * @param Iso<mixed, string> $iso
     * @psalm-param mixed $value
     */
    public function __construct(
        private readonly Context $context,
        private readonly Iso $iso,
        private readonly mixed $value
    ) {
    }

    /**
     * @return Generator<bool>
     */
    public function __invoke(XMLWriter $writer): Generator
    {
        yield from children([
            $this->buildXsiType(...),
            value($this->iso->to($this->value))
        ])($writer);
    }

    /**
     * @return Generator<bool>
     */
    private function buildXsiType(XMLWriter $writer): Generator
    {
        if ($this->context->bindingUse !== BindingUse::ENCODED) {
            return;
        }

        yield from (new XsiAttributeBuilder(
            $this->context,
            XsiTypeDetector::detectFromValue($this->context, $this->value),
            includeXsiTargetNamespace:  !$this->context->type->getMeta()->isQualified()->unwrapOr(false)
        ))($writer);
    }
}
