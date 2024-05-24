<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Model\Definitions\SoapVersion;
use Soap\Xml\Xmlns;
use VeeWee\Xml\Writer\Writer;
use function VeeWee\Xml\Writer\Builder\children;
use function VeeWee\Xml\Writer\Builder\namespaced_element;
use function VeeWee\Xml\Writer\Mapper\memory_output;

final class SoapEnvelopeWriter
{
    /**
     * @param \Closure(\XMLWriter): \Generator<bool> $children
     */
    public function __construct(
        private readonly SoapVersion $soapVersion,
        private readonly BindingUse $bindingUse,
        private readonly \Closure $children
    ) {
    }

    public function __invoke(): string
    {
        $envelopeNamespace = match($this->soapVersion) {
            SoapVersion::SOAP_11 => Xmlns::soap11Envelope()->value(),
            SoapVersion::SOAP_12 => rtrim(Xmlns::soap12Envelope()->value(), '/'), // TODO : Both could be accepted but the one without slashes should be the main one.
        };

        return Writer::inMemory()
            ->write(
                namespaced_element(
                    $envelopeNamespace,
                    'SOAP-ENV',
                    'Envelope',
                    children([
                        // TODO : attribute('encodingStyle', $this->bindingUse->toEncodingStyle()),
                        namespaced_element(
                            $envelopeNamespace,
                            'SOAP-ENV',
                            'Body',
                            $this->children
                        )
                    ])
                )
            )
            ->map(memory_output());
    }
}
