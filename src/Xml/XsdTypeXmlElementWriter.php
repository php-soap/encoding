<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml;

use Generator;
use Soap\Encoding\Xml\Writer\ElementBuilder;
use Soap\Engine\Metadata\Model\XsdType;
use VeeWee\Xml\Writer\Writer;
use XMLWriter;
use function VeeWee\Xml\Writer\Mapper\memory_output;

final class XsdTypeXmlElementWriter
{
    /**
     * @param callable(XMLWriter): Generator<bool> $children
     */
    public function __invoke(XsdType $type, callable $children): string
    {
        return Writer::inMemory()
            ->write(new ElementBuilder($type, $children(...)))
            ->map(memory_output());
    }
}
