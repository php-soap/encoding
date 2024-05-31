<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\TypeInference\XsiTypeDetector;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use VeeWee\Reflecta\Iso\Iso;
use function VeeWee\Xml\Writer\Builder\children;
use function VeeWee\Xml\Writer\Builder\value;

final class ElementValueBuilder
{
    public function __construct(
        private readonly Context $context,
        private readonly Iso $iso,
        private readonly mixed $value
    ) {
    }

    /**
     * @return \Generator<true>
     */
    public function __invoke(\XMLWriter $writer): \Generator
    {
        yield from children([
            $this->buildXsiType(...),
            value($this->iso->to($this->value))
        ])($writer);
    }

    /**
     * @return \Generator<bool>
     */
    private function buildXsiType(\XMLWriter $writer): \Generator
    {
        if ($this->context->bindingUse !== BindingUse::ENCODED) {
            return;
        }

        yield from (new XsiAttributeBuilder(
            $this->context,
            XsiTypeDetector::detectFromValue($this->context, $this->value))
        )($writer);
    }
}
