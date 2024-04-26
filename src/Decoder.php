<?php
declare(strict_types=1);

namespace Soap\Encoding;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Xml\Reader\SoapEnvelopeReader;
use Soap\Engine\Decoder as SoapDecoder;
use Soap\Engine\HttpBinding\SoapResponse;
use Soap\Engine\Metadata\Metadata;
use Soap\WsdlReader\Model\Definitions\BindingUse;

final class Decoder implements SoapDecoder
{
    public function __construct(
        private readonly Metadata $metadata,
        private readonly EncoderRegistry $registry
    ) {
    }

    /**
     * @return mixed
     */
    public function decode(string $method, SoapResponse $response)
    {
        // TODO  : invariants
        // | outputBindingUsage  | literal                                                                                                |
        // | bindingStyle       | document
        // SEE https://www.ibm.com/docs/en/bpm/8.5.7?topic=files-wsdl-binding-styles                                                                             |                                                                                |

        $methodInfo = $this->metadata->getMethods()->fetchByName($method);
        $meta = $methodInfo->getMeta();
        $bindingUse = $meta->outputBindingUsage()->map(BindingUse::from(...))->unwrapOr(BindingUse::LITERAL);

        $returnType = $methodInfo->getReturnType();
        $context = new Context($returnType, $this->metadata, $this->registry, $bindingUse);
        $decoder = $this->registry->detectEncoderForContext($context);

        // TODO / Strip the body before parsing the payload? To investigate what the payload is exactly.
        $body = (new SoapEnvelopeReader())($response->getPayload());

        return $decoder->iso($context)->from($body);
    }
}
