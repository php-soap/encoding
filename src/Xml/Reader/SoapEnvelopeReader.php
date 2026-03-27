<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Reader;

use Soap\Encoding\Fault\Guard\SoapFaultGuard;
use Soap\Encoding\Xml\Node\Element;
use Soap\Xml\Locator\SoapBodyLocator;
use VeeWee\Xml\Dom\Document;
use function VeeWee\Xml\Dom\Assert\assert_element;
use function VeeWee\Xml\Dom\Loader\xml_string_loader;

final class SoapEnvelopeReader
{
    /**
     * @param non-empty-string $xml
     * @param int $libXmlOptions - bitmask of LIBXML_* constants https://www.php.net/manual/en/libxml.constants.php
     */
    public function __invoke(string $xml, int $libXmlOptions = 0): Element
    {
        $envelope = Document::fromLoader(xml_string_loader($xml, $libXmlOptions));

        // Make sure it does not contain a fault response before parsing the body parts.
        (new SoapFaultGuard())($envelope);

        // Locate all body parts:
        $body = assert_element($envelope->locate(new SoapBodyLocator()));

        return Element::fromDOMElement($body);
    }
}
