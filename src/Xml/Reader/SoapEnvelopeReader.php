<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Reader;

use Soap\Xml\Locator\SoapBodyLocator;
use VeeWee\Xml\Dom\Document;

final class SoapEnvelopeReader
{
    public function __invoke(string $xml): string
    {
        $document = Document::fromXmlString($xml);
        $body = $document->locate(new SoapBodyLocator());

        return (new ChildrenReader())(Document::fromXmlNode($body)->toXmlString());
    }
}
