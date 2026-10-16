<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Generator;
use Soap\Encoding\Encoder\Context;
use VeeWee\Xml\Exception\RuntimeException;
use XMLWriter;

final class XsdTypeXmlElementWriter
{
    /**
     * @param callable(XMLWriter): Generator<bool> $children
     *
     * @throws RuntimeException
     *
     * @return non-empty-string
     */
    public function __invoke(Context $context, callable $children): string
    {
        return (new InMemoryXmlWriter())(new ElementBuilder($context, $children(...)));
    }
}
