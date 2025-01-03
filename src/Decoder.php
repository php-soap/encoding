<?php
declare(strict_types=1);

namespace Soap\Encoding;

use Soap\Encoding\Encoder\Method\MethodContext;
use Soap\Encoding\Encoder\Method\ResponseEncoder;
use Soap\Engine\Decoder as SoapDecoder;
use Soap\Engine\HttpBinding\SoapResponse;
use Soap\Engine\Metadata\Metadata;
use Soap\WsdlReader\Model\Definitions\Namespaces;
use function count;

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
        $methodContext = new MethodContext($methodInfo, $this->metadata, $this->registry, $this->namespaces);
        $iso = (new ResponseEncoder())->iso($methodContext);

        $payload = $response->getPayload();
        /** @var list<mixed> $parts */
        $parts = $iso->from($payload);

        return match(count($parts)) {
            0 => null,
            1 => $parts[0],
            default => $parts,
        };
    }
}
