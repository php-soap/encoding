<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Reader;

use Soap\Xml\Locator\SoapBodyLocator;
use VeeWee\Xml\Dom\Document;
use function Psl\Str\join;
use function VeeWee\Xml\Dom\Locator\Element\children as locateChildElements;

final class SoapEnvelopeReader
{
    public function __invoke(string $xml): string
    {
        $document = Document::fromXmlString($xml);
        $body = $document->locate(new SoapBodyLocator());

        $elements = locateChildElements($body);

        // TODO : What to do with multiple elements... It's not XML valid to have multiple root elements
        return join(
            $elements->map(
                static fn(\DOMElement $element): string => $document->stringifyNode($element)
            ),
            ''
        );
    }
}
