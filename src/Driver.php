<?php
declare(strict_types=1);

namespace Soap\Encoding;

use Soap\Engine\Driver as SoapDriver;
use Soap\Engine\HttpBinding\SoapRequest;
use Soap\Engine\HttpBinding\SoapResponse;
use Soap\Engine\Metadata\Metadata;

class Driver implements SoapDriver
{
    public function __construct(
        private readonly Encoder $encoder,
        private readonly Decoder $decoder,
        private readonly Metadata $metadata,
    ){
    }

    public static function createFromMetadata(Metadata $metadata, ?EncoderRegistry $registry = null): self
    {
        $registry ??= EncoderRegistry::default();

        return new self(
            new Encoder($metadata, $registry),
            new Decoder($metadata, $registry),
            $metadata,
        );
    }


    public function decode(string $method, SoapResponse $response)
    {
        return $this->decoder->decode($method, $response);
    }

    public function encode(string $method, array $arguments): SoapRequest
    {
        return $this->encoder->encode($method, $arguments);
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }
}
