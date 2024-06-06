<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Reader;

use Soap\Xml\Locator\SoapBodyLocator;
use VeeWee\Xml\Dom\Document;
use function VeeWee\Xml\Dom\Assert\assert_element;

final class SoapEnvelopeReader
{
    /**
     * @param non-empty-string $xml
     */
    public function __invoke(string $xml): string
    {
        $document = Document::fromXmlString($xml);
        $body = assert_element($document->locate(new SoapBodyLocator()));

        return (new ChildrenReader())(Document::fromXmlNode($body)->toXmlString());
    }
}
