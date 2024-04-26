<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Generator;
use VeeWee\Xml\Xmlns\Xmlns;
use function VeeWee\Xml\Writer\Builder\namespaced_attribute;

final class NilAttributeBuilder
{
    /**
     * @return Generator<bool>
     */
    public function __invoke(\XMLWriter $writer): Generator
    {
        yield from namespaced_attribute(
            Xmlns::xsi()->value(),
            'xsi',
            'nil',
            'true'
        )($writer);
    }
}
