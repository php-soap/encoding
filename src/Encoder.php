<?php
declare(strict_types=1);

namespace Soap\Encoding;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Xml\Writer\SoapEnvelopeWriter;
use Soap\Engine\Encoder as SoapEncoder;
use Soap\Engine\HttpBinding\SoapRequest;
use Soap\Engine\Metadata\Metadata;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Model\Definitions\SoapVersion;
use function VeeWee\Reflecta\Lens\index;

final class Encoder implements SoapEncoder
{
    public function __construct(
        private readonly Metadata $metadata,
        private readonly EncoderRegistry $registry
    ) {
    }

    public function encode(string $method, array $arguments): SoapRequest
    {
        // TODO  : invariants
        // | inputBindingUsage  | literal                                                                                                |
        // | bindingStyle       | document
        // SEE https://www.ibm.com/docs/en/bpm/8.5.7?topic=files-wsdl-binding-styles                                                                             |

        $methodInfo = $this->metadata->getMethods()->fetchByName($method);
        $meta = $methodInfo->getMeta();

        // TODO : What on failure? Is fallback assumption OK or error?
        $soapVersion = $meta->soapVersion()->map(SoapVersion::from(...))->unwrapOr(SoapVersion::SOAP_12);
        $bindingUse = $meta->inputBindingUsage()->map(BindingUse::from(...))->unwrapOr(BindingUse::LITERAL);

        $request = [];
        foreach ($methodInfo->getParameters() as $index => $parameter)
        {
            $type = $parameter->getType();
            $context = new Context($type, $this->metadata, $this->registry, $bindingUse);
            $argument = index($index)->get($arguments);
            $request[] = $this->registry->detectEncoderForContext($context)->iso($context)->to($argument);
        }

        // TODO : Set up headers:
        // TODO : Check if headers should be in request payload.
        $headers = [
            /*
             * SOAP 1.1
             *      * Content-Type: text/xml; charset=utf-8
                    * SOAPAction: "AddressDoctor/Webservice5/v2/Process"
             */

            /***
             * SOAP 1.2
             *
             *  Content-Type: application/soap+xml; charset=utf-8; action="AddressDoctor/Webservice5/v2/Process"
             */
        ];

        // TODO : unwrap or throw very specific issue or fallback to a specific soap version?
        $writeEnvelope = new SoapEnvelopeWriter($soapVersion);

        return new SoapRequest(
            implode("\r\n", $headers)."\r\n\r\n".$writeEnvelope(implode('', $request)),
            $meta->location()->unwrap(),
            $meta->action()->unwrap(),
            // TODO : Dont use constants. Make them available through enum directly.
            match($soapVersion) {
                SoapVersion::SOAP_11 => \SOAP_1_1,
                SoapVersion::SOAP_12 => \SOAP_1_2,
            },
            $meta->isOneWay()->unwrapOr(false)
        );
    }
}
