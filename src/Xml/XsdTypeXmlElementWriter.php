<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml;

use Generator;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Xml\Writer\EncodedElementBuilder;
use Soap\Encoding\Xml\Writer\LiteralElementBuilder;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use VeeWee\Xml\Writer\Writer;
use XMLWriter;
use function VeeWee\Xml\Writer\Mapper\memory_output;

final class XsdTypeXmlElementWriter
{
    /**
     * @param callable(XMLWriter): Generator<bool> $children
     */
    public function __invoke(Context $context, callable $children): string
    {
        return Writer::inMemory()
            ->write(match($context->bindingUse) {
                BindingUse::ENCODED => new EncodedElementBuilder($context, $children(...)),
                default => new LiteralElementBuilder($context, $children(...)),
            })
            ->map(memory_output());
    }
}
