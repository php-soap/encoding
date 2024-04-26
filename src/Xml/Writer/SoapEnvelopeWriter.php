<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Soap\WsdlReader\Model\Definitions\SoapVersion;
use VeeWee\Xml\Writer\Writer;
use function VeeWee\Xml\Writer\Builder\children;
use function VeeWee\Xml\Writer\Builder\namespaced_element;
use function VeeWee\Xml\Writer\Builder\raw;
use function VeeWee\Xml\Writer\Mapper\memory_output;

final class SoapEnvelopeWriter
{
    public function __construct(
        private readonly SoapVersion $soapVersion
    ) {
    }

    public function __invoke(string $xml): string
    {
        return Writer::inMemory()
            ->write(
                namespaced_element(
                    $this->soapVersion->value,
                    'soap',
                    'Envelope',
                    children([
                        namespaced_element(
                            $this->soapVersion->value,
                            'soap',
                            'Body',
                            children([
                                raw($xml)
                            ])
                        )
                    ])
                )
            )
            ->map(memory_output());
    }
}
