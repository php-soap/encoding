<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Generator;
use Soap\Encoding\Encoder\Context;
use Soap\Engine\Metadata\Model\MethodMeta;
use Soap\Engine\Metadata\Model\TypeMeta;
use Soap\WsdlReader\Model\Definitions\BindingStyle;
use XMLWriter;
use function Psl\Type\non_empty_string;
use function VeeWee\Xml\Writer\Builder\raw;

final class ParameterBuilder
{
    public function __construct(
        private readonly MethodMeta $meta,
        private readonly Context $context,
        private readonly mixed $value
    ) {
    }

    /**
     * @return Generator<bool>
     */
    public function __invoke(XMLWriter $writer): Generator
    {
        $bindingStyle = BindingStyle::tryFrom($this->meta->bindingStyle()->unwrapOr(BindingStyle::DOCUMENT->value));

        yield from match($bindingStyle) {
            BindingStyle::DOCUMENT => $this->buildDocument($writer),
            BindingStyle::RPC => $this->buildRpc($writer),
        };
    }

    /**
     * @return Generator<bool>
     */
    private function buildDocument(XMLWriter $writer): Generator
    {
        $type = $this->context->type;
        $context = $this->context->withType(
            $type
                ->withXmlTargetNodeName($type->getName())
                ->withMeta(
                    static fn (TypeMeta $meta) => $meta->withIsQualified(true)
                )
        );

        yield from raw($this->encode($context))($writer);
    }

    /**
     * @return Generator<bool>
     */
    private function buildRpc(XMLWriter $writer): Generator
    {
        yield from raw($this->encode($this->context))($writer);
    }

    /**
     * @return non-empty-string
     */
    private function encode(Context $context): string
    {
        return non_empty_string()->assert(
            $context->registry->detectEncoderForContext($context)->iso($context)->to($this->value)
        );
    }
}
