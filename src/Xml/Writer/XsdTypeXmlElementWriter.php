<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Generator;
use Soap\Encoding\Encoder\Context;
use VeeWee\Xml\Writer\Writer;
use XMLWriter;
use function VeeWee\Xml\Writer\Mapper\memory_output;

final class XsdTypeXmlElementWriter
{
    /**
     * @param callable(XMLWriter): Generator<bool> $children
     *
     * @return non-empty-string
     */
    public function __invoke(Context $context, callable $children): string
    {
        /** @psalm-var non-empty-string */
        return Writer::inMemory()
            ->write(new ElementBuilder($context, $children(...)))
            ->map(memory_output());
    }
}
