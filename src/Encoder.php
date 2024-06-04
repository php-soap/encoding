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
use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Model\Definitions\EncodingStyle;
use Soap\WsdlReader\Model\Definitions\Namespaces;
use Soap\WsdlReader\Model\Definitions\SoapVersion;
use function VeeWee\Reflecta\Lens\index;

final class Encoder implements SoapEncoder
{
    public function __construct(
        private readonly Metadata $metadata,
        private readonly Namespaces $namespaces,
        private readonly EncoderRegistry $registry
    ) {
    }

    public function encode(string $method, array $arguments): SoapRequest
    {
        $methodInfo = $this->metadata->getMethods()->fetchByName($method);
        $meta = $methodInfo->getMeta();

        // TODO : What on failure? Is fallback assumption OK or error?
        $soapVersion = $meta->soapVersion()->map(SoapVersion::from(...))->unwrapOr(SoapVersion::SOAP_12);
        $bindingUse = $meta->inputBindingUsage()->map(BindingUse::from(...))->unwrapOr(BindingUse::LITERAL);
        $encodingStyle = $meta->inputEncodingStyle()->map(EncodingStyle::tryFrom(...));

        $request = [];
        foreach ($methodInfo->getParameters() as $index => $parameter)
        {
            $type = $parameter->getType();
            $context = new Context($type, $this->metadata, $this->registry, $this->namespaces, $bindingUse);
            $argument = index($index)->get($arguments);

            $request[] = $this->registry->detectEncoderForContext($context)->iso($context)->to($argument);
        }

        $operation = new OperationBuilder($meta, $this->namespaces, $request);

        // TODO : unwrap or throw very specific issue or fallback to a specific soap version?
        $writeEnvelope = new SoapEnvelopeWriter($soapVersion, $bindingUse, $encodingStyle, $operation(...));

        return new SoapRequest(
            $writeEnvelope() . PHP_EOL,
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
