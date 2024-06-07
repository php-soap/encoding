<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Reader;

use Soap\Encoding\Fault\Guard\SoapFaultGuard;
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
        $envelope = Document::fromXmlString($xml);

        // Make sure it does not contain a fault response before parsing the body parts.
        (new SoapFaultGuard())($envelope);

        // Locate all body parts:
        $body = assert_element($envelope->locate(new SoapBodyLocator()));

        return (new ChildrenReader())(Document::fromXmlNode($body)->toXmlString());
    }
}
