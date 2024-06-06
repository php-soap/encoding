<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Reader;

use DOMElement;
use VeeWee\Xml\Dom\Document;
use function Psl\Str\join;
use function VeeWee\Xml\Dom\Locator\Element\children as locateChildElements;

final class ChildrenReader
{
    /**
     * @param non-empty-string $xml
     */
    public function __invoke(string $xml): string
    {
        $document = Document::fromXmlString($xml);
        $elements = locateChildElements($document->locateDocumentElement());

        return join(
            $elements->map(
                static fn (DOMElement $element): string => Document::fromXmlNode($element)->stringifyDocumentElement(),
            ),
            ''
        );
    }
}
