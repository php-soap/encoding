<?php
declare(strict_types=1);

namespace Soap\Encoding\Fault\Encoder;

use Soap\Encoding\Fault\Soap11Fault;
use Soap\Encoding\Restriction\WhitespaceRestriction;
use Soap\Xml\Xmlns;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Xml\Dom\Document;
use VeeWee\Xml\Writer\Writer;
use function Psl\invariant;
use function VeeWee\Xml\Dom\Xpath\Configurator\namespaces;
use function VeeWee\Xml\Writer\Builder\children;
use function VeeWee\Xml\Writer\Builder\element;
use function VeeWee\Xml\Writer\Builder\namespaced_element;
use function VeeWee\Xml\Writer\Builder\raw;
use function VeeWee\Xml\Writer\Builder\value;
use function VeeWee\Xml\Writer\Mapper\memory_output;

/**
 * @implements SoapFaultEncoder<Soap11Fault>
 */
final class Soap11FaultEncoder implements SoapFaultEncoder
{
    /**
     * @return Iso<Soap11Fault, non-empty-string>
     */
    public function iso(): Iso
    {
        return new Iso(
            $this->to(...),
            $this->from(...)
        );
    }

    /**
     * @return non-empty-string
     */
    private function to(Soap11Fault $fault): string
    {
        $envNamespace = Xmlns::soap11Envelope()->value();

        /** @var non-empty-string */
        return Writer::inMemory()
            ->write(children([
                namespaced_element(
                    $envNamespace,
                    'env',
                    'Fault',
                    children([
                        element(
                            'faultcode',
                            value($fault->faultCode),
                        ),
                        element(
                            'faultstring',
                            value($fault->faultString),
                        ),
                        ...(
                            $fault->faultActor !== null
                            ? [
                                element(
                                    'faultactor',
                                    value($fault->faultActor)
                                )
                            ]
                            : []
                        ),
                        ...($fault->detail !== null ? [raw($fault->detail)] : []),
                    ])
                )
            ]))
            ->map(memory_output());
    }

    /**
     * @param non-empty-string $fault
     */
    private function from(string $fault): Soap11Fault
    {
        $document = Document::fromXmlString($fault);
        $documentElement = $document->locateDocumentElement();

        $envelopeUri = $documentElement->namespaceURI;
        invariant($envelopeUri !== null, 'No SoapFault envelope namespace uri was specified.');
        $xpath = $document->xpath(namespaces(['env' => $envelopeUri]));

        $actor = $xpath->query('./faultactor');
        $detail = $xpath->query('./detail');

        return new Soap11Fault(
            faultCode: WhitespaceRestriction::collapse($xpath->querySingle('./faultcode')->textContent),
            faultString: WhitespaceRestriction::collapse($xpath->querySingle('./faultstring')->textContent),
            faultActor: $actor->count() ? trim($actor->expectFirst()->textContent) : null,
            detail: $detail->count() ? Document::fromXmlNode($detail->expectFirst())->stringifyDocumentElement() : null,
        );
    }
}
