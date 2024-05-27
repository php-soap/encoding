<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Psl\Option\Option;
use Soap\WsdlReader\Model\Definitions\EncodingStyle;
use Soap\WsdlReader\Model\Definitions\SoapVersion;
use Soap\Xml\Xmlns;
use VeeWee\Xml\Writer\Writer;
use function Psl\Vec\filter_nulls;
use function VeeWee\Xml\Writer\Builder\children;
use function VeeWee\Xml\Writer\Builder\namespace_attribute;
use function VeeWee\Xml\Writer\Builder\namespaced_attribute;
use function VeeWee\Xml\Writer\Builder\namespaced_element;
use function VeeWee\Xml\Writer\Mapper\memory_output;

final class SoapEnvelopeWriter
{
    /**
     * @param Option<EncodingStyle> $encodingStyle
     * @param \Closure(\XMLWriter): \Generator<bool> $children
     */
    public function __construct(
        private readonly SoapVersion $soapVersion,
        private readonly Option $encodingStyle,
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
                    namespaced_element(
                        $envelopeNamespace,
                        'SOAP-ENV',
                        'Body',
                        children(
                            filter_nulls([
                                // In SOAP 1.2 the position of the encoding attributes is limited:
                                // See: https://www.w3.org/TR/soap12-part1/#soapencattr
                                // For SOAP 1.1 it can be everywhere:
                                // See: https://www.w3.org/TR/2000/NOTE-SOAP-20000508/#_Toc478383495
                                $this->encodingStyle->map(
                                    static fn (EncodingStyle $encodingStyle) => children([
                                        namespace_attribute(
                                            $encodingStyle->value,
                                            'SOAP-ENC'
                                        ),
                                        namespaced_attribute(
                                            $envelopeNamespace,
                                            'SOAP-ENV',
                                            'encodingStyle',
                                            $encodingStyle->value
                                        )
                                    ])
                                )->unwrapOr(null),
                                $this->children
                            ])
                        )
                    )
                )
            )
            ->map(memory_output());
    }
}
