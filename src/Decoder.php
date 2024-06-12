<?php
declare(strict_types=1);

namespace Soap\Encoding;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Xml\Reader\OperationReader;
use Soap\Engine\Decoder as SoapDecoder;
use Soap\Engine\HttpBinding\SoapResponse;
use Soap\Engine\Metadata\Metadata;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Model\Definitions\Namespaces;
use function count;
use function Psl\invariant;
use function Psl\Vec\map;

final class Decoder implements SoapDecoder
{
    public function __construct(
        private readonly Metadata $metadata,
        private readonly Namespaces $namespaces,
        private readonly EncoderRegistry $registry
    ) {
    }

    /**
     * @psalm-return mixed
     */
    public function decode(string $method, SoapResponse $response): mixed
    {
        $methodInfo = $this->metadata->getMethods()->fetchByName($method);
        $meta = $methodInfo->getMeta();
        $bindingUse = $meta->outputBindingUsage()->map(BindingUse::from(...))->unwrapOr(BindingUse::LITERAL);

        $returnType = $methodInfo->getReturnType();
        $context = new Context($returnType, $this->metadata, $this->registry, $this->namespaces, $bindingUse);
        $decoder = $this->registry->detectEncoderForContext($context);
        $iso = $decoder->iso($context);

        // The SoapResponse only contains the payload of the response (with no headers).
        // It can be parsed directly as XML.
        $payload = $response->getPayload();
        invariant($payload !== '', 'Expected a non-empty response payload. Received an empty HTTP response');
        $parts = (new OperationReader($meta))($payload)->elements();

        return match(count($parts)) {
            0 => null,
            1 => $iso->from($parts[0]),
            default => map(
                $parts,
                static fn (string $part): mixed => $iso->from($part)
            ),
        };
    }
}
