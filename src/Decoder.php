<?php
declare(strict_types=1);

namespace Soap\Encoding;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Xml\Reader\OperationReader;
use Soap\Engine\Decoder as SoapDecoder;
use Soap\Engine\HttpBinding\SoapResponse;
use Soap\Engine\Metadata\Metadata;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use function Psl\Vec\map;

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
    public function decode(string $method, SoapResponse $response): mixed
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

        // The SoapResponse only contains the payload of the response (with no headers).
        // It can be parsed directly as XML.
        $parts = (new OperationReader($meta))($response->getPayload());

        return match(count($parts)) {
            0 => null,
            1 => $decoder->iso($context)->from($parts[0]),
            default => map($parts, $decoder->iso($context)->from(...)),
        };
    }
}
