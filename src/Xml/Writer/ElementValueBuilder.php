<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Generator;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\Feature\CData;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\TypeInference\XsiTypeDetector;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use XMLWriter;
use function VeeWee\Xml\Writer\Builder\cdata;
use function VeeWee\Xml\Writer\Builder\children;
use function VeeWee\Xml\Writer\Builder\value;

final class ElementValueBuilder
{
    /**
     * @param XmlEncoder<mixed, string> $encoder
     * @psalm-param mixed $value
     */
    public function __construct(
        private readonly Context $context,
        private readonly XmlEncoder $encoder,
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
            $this->buildValue(...),
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

        $context = $this->context;
        $type = $context->type;

        yield from (new XsiAttributeBuilder(
            $this->context,
            XsiTypeDetector::detectFromValue($context, $this->value),
            includeXsiTargetNamespace: $type->getXmlTargetNamespace() !== $type->getXmlNamespace()
                || !$type->getMeta()->isQualified()->unwrapOr(false)
        ))($writer);
    }

    /**
     * @return Generator<bool>
     */
    private function buildValue(XMLWriter $writer): Generator
    {
        $encoded = $this->encoder->iso($this->context)->to($this->value);

        $builder = match (true) {
            $this->encoder instanceof CData => cdata(value($encoded)),
            default => value($encoded)
        };

        yield from $builder($writer);
    }
}
