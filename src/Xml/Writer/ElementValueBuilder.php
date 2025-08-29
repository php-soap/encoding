<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Generator;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\Feature;
use Soap\Encoding\Encoder\XmlEncoder;
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
        yield from XsiAttributeBuilder::forEncodedValue(
            $this->context,
            $this->encoder,
            $this->value,
        )($writer);
    }

    /**
     * @deprecated Use XsiAttributeBuilder::resolveXsiTypeForValue() instead. Will be removed in 1.0.0.
     */
    public static function resolveXsiTypeForValue(Context $context, mixed $value): string
    {
        return XsiAttributeBuilder::resolveXsiTypeForValue($context, $value);
    }

    /**
     * @deprecated Use XsiAttributeBuilder::shouldIncludeXsiTargetNamespace() instead. Will be removed in 1.0.0.
     */
    public static function shouldIncludeXsiTargetNamespace(Context $context): bool
    {
        return XsiAttributeBuilder::shouldIncludeXsiTargetNamespace($context);
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
