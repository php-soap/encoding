<?php
declare(strict_types=1);

namespace Soap\Encoding\Fault\Guard;

use Soap\Encoding\Exception\SoapFaultException;
use Soap\Encoding\Fault\Encoder\Soap11FaultEncoder;
use Soap\Encoding\Fault\Encoder\Soap12FaultEncoder;
use Soap\Xml\Xmlns;
use VeeWee\Xml\Dom\Document;
use function Psl\invariant;
use function VeeWee\Xml\Dom\Xpath\Configurator\namespaces;

final class SoapFaultGuard
{
    /**
     * @throws SoapFaultException
     */
    public function __invoke(Document $envelope): void
    {
        $envelopeUri = $envelope->locateDocumentElement()->namespaceURI;
        invariant($envelopeUri !== null, 'No SoapFault envelope namespace uri was specified.');
        $xpath = $envelope->xpath(namespaces([
            'env' => $envelopeUri,
        ]));

        $fault = $xpath->query('//env:Fault');
        if (!$fault->count()) {
            return;
        }

        $faultXml = Document::fromXmlNode($fault->expectFirst())->stringifyDocumentElement();

        $fault = match($envelopeUri) {
            Xmlns::soap11Envelope()->value() => (new Soap11FaultEncoder())->iso()->from($faultXml),
            default => (new Soap12FaultEncoder())->iso()->from($faultXml),
        };

        throw new SoapFaultException($fault);
    }
}
