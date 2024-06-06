<?php
declare(strict_types=1);

namespace Soap\Encoding\Fault\Encoder;

use Soap\Encoding\Fault\Soap12Fault;
use Soap\Encoding\Restriction\WhitespaceRestriction;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Xml\Dom\Document;
use VeeWee\Xml\Writer\Writer;
use function Psl\invariant;
use function VeeWee\Xml\Dom\Xpath\Configurator\namespaces;
use function VeeWee\Xml\Writer\Builder\children;
use function VeeWee\Xml\Writer\Builder\namespaced_element;
use function VeeWee\Xml\Writer\Builder\prefixed_element;
use function VeeWee\Xml\Writer\Builder\raw;
use function VeeWee\Xml\Writer\Builder\value;
use function VeeWee\Xml\Writer\Mapper\memory_output;

/**
 * @implements SoapFaultEncoder<Soap12Fault>
 */
final class Soap12FaultEncoder implements SoapFaultEncoder
{
    private const ENV_NAMESPACE = 'http://www.w3.org/2003/05/soap-envelope';

    /**
     * @return Iso<Soap12Fault, non-empty-string>
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
    private function to(Soap12Fault $fault): string
    {
        /** @var non-empty-string */
        return Writer::inMemory()
            ->write(children([
                namespaced_element(
                    self::ENV_NAMESPACE,
                    'env',
                    'Fault',
                    children([
                        prefixed_element(
                            'env',
                            'Code',
                            children([
                                prefixed_element(
                                    'env',
                                    'Value',
                                    value($fault->code)
                                ),
                                ...(
                                    $fault->subCode !== null
                                    ? [
                                        prefixed_element(
                                            'env',
                                            'Subcode',
                                            children([
                                                prefixed_element(
                                                    'env',
                                                    'Value',
                                                    value($fault->subCode)
                                                )
                                            ])
                                        )
                                    ]
                                    : []
                                ),

                            ])
                        ),
                        prefixed_element(
                            'env',
                            'Reason',
                            children([
                                prefixed_element(
                                    'env',
                                    'Text',
                                    value($fault->reason)
                                )
                            ])
                        ),
                        ...(
                            $fault->node !== null
                                ? [
                                    prefixed_element(
                                        'env',
                                        'Node',
                                        value($fault->node)
                                    )
                                ]
                                : []
                        ),
                        ...(
                            $fault->role !== null
                                ? [
                                    prefixed_element(
                                        'env',
                                        'Role',
                                        value($fault->role)
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
    private function from(string $fault): Soap12Fault
    {
        $document = Document::fromXmlString($fault);
        $documentElement = $document->locateDocumentElement();

        $envelopeUri = $documentElement->namespaceURI;
        invariant($envelopeUri !== null, 'No SoapFault envelope namespace uri was specified.');
        $xpath = $document->xpath(namespaces(['env' => $envelopeUri]));

        $subCode = $xpath->query('./env:Code/env:Subcode/env:Value');
        $node = $xpath->query('./env:Node');
        $role = $xpath->query('./env:Role');
        $detail = $xpath->query('./env:Detail');

        return new Soap12Fault(
            code: WhitespaceRestriction::collapse($xpath->querySingle('./env:Code/env:Value')->textContent),
            reason: WhitespaceRestriction::collapse($xpath->querySingle('./env:Reason/env:Text')->textContent),
            subCode: $subCode->count() ? WhitespaceRestriction::collapse($subCode->expectFirst()->textContent) : null,
            node: $node->count() ? trim($node->expectFirst()->textContent) : null,
            role: $role->count() ? trim($role->expectFirst()->textContent) : null,
            detail: $detail->count() ? Document::fromXmlNode($detail->expectFirst())->stringifyDocumentElement() : null,
        );
    }
}
