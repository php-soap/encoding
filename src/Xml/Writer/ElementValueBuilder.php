<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Generator;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\Feature;
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
        [$xsiType, $includeXsiTargetNamespace] = match(true) {
            $this->encoder instanceof Feature\XsiTypeCalculator => [
                $this->encoder->resolveXsiTypeForValue($context, $this->value),
                $this->encoder->shouldIncludeXsiTargetNamespace($context),
            ],
            default => [
                self::resolveXsiTypeForValue($context, $this->value),
                self::shouldIncludeXsiTargetNamespace($context),
            ],
        };

        yield from (new XsiAttributeBuilder(
            $this->context,
            $xsiType,
            $includeXsiTargetNamespace,
        ))($writer);
    }

    /**
     * Can be used as a default fallback function when implementing the XsiTypeCalculator interface.
     * Tells the XsiAttributeBuilder what xsi:type attribute should be set to for a given value.
     */
    public static function resolveXsiTypeForValue(Context $context, mixed $value): string
    {
        return XsiTypeDetector::detectFromValue($context, $value);
    }

    /**
     * Can be used as a default fallback function when implementing the XsiTypeCalculator interface.
     * Tells the XsiAttributeBuilder that the prefix of the xsi:type should be imported as a xmlns namespace.
     */
    public static function shouldIncludeXsiTargetNamespace(Context $context): bool
    {
        $type = $context->type;

        return $type->getXmlTargetNamespace() !== $type->getXmlNamespace()
            || !$type->getMeta()->isQualified()->unwrapOr(false);
    }

    /**
     * @return Generator<bool>
     */
    private function buildValue(XMLWriter $writer): Generator
    {
        $encoded = $this->encoder->iso($this->context)->to($this->value);

        $builder = match (true) {
            $this->encoder instanceof Feature\CData => cdata(value($encoded)),
            default => value($encoded)
        };

        yield from $builder($writer);
    }
}
