<?php
declare(strict_types=1);

namespace Soap\Encoding;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Xml\Writer\OperationBuilder;
use Soap\Encoding\Xml\Writer\SoapEnvelopeWriter;
use Soap\Engine\Encoder as SoapEncoder;
use Soap\Engine\HttpBinding\SoapRequest;
use Soap\Engine\Metadata\Metadata;
use Soap\Engine\Metadata\Model\TypeMeta;
use Soap\WsdlReader\Model\Definitions\BindingStyle;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Model\Definitions\SoapVersion;
use function VeeWee\Reflecta\Lens\index;
use function VeeWee\Xml\Writer\Builder\raw;

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

        $operation = new OperationBuilder($meta, $request);

        // TODO : unwrap or throw very specific issue or fallback to a specific soap version?
        $writeEnvelope = new SoapEnvelopeWriter($soapVersion, $bindingUse, $operation(...));

        return new SoapRequest(
            $writeEnvelope(),
            $meta->location()->unwrap(),
            $meta->action()->unwrap(),
            match($soapVersion) {
                SoapVersion::SOAP_11 => SoapRequest::SOAP_1_1,
                SoapVersion::SOAP_12 => SoapRequest::SOAP_1_2,
            },
            $meta->isOneWay()->unwrapOr(false)
        );
    }
}
