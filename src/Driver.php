<?php
declare(strict_types=1);

namespace Soap\Encoding;

use Soap\Engine\Driver as SoapDriver;
use Soap\Engine\HttpBinding\SoapRequest;
use Soap\Engine\HttpBinding\SoapResponse;
use Soap\Engine\Metadata\Metadata;
use Soap\WsdlReader\Locator\ServiceSelectionCriteria;
use Soap\WsdlReader\Metadata\Wsdl1MetadataProvider;
use Soap\WsdlReader\Model\Definitions\Namespaces;
use Soap\WsdlReader\Model\Wsdl1;

class Driver implements SoapDriver
{
    public function __construct(
        private readonly Encoder $encoder,
        private readonly Decoder $decoder,
        private readonly Metadata $metadata,
    ){
    }

    public static function createFromWsdl1(
        Wsdl1 $wsdl,
        ?ServiceSelectionCriteria $serviceSelectionCriteria = null,
        ?EncoderRegistry $registry = null
    ) {
        $registry ??= EncoderRegistry::default();
        $metadataProvider = new Wsdl1MetadataProvider(
            $wsdl,
            $serviceSelectionCriteria
        );
        $metadata = $metadataProvider->getMetadata();

        return new self(
            new Encoder($metadata, $wsdl->namespaces, $registry),
            new Decoder($metadata, $wsdl->namespaces, $registry),
            $metadata,
        );
    }

    public static function createFromMetadata(
        Metadata $metadata,
        Namespaces $namespaces,
        ?EncoderRegistry $registry = null
    ): self {
        $registry ??= EncoderRegistry::default();

        return new self(
            new Encoder($metadata, $namespaces, $registry),
            new Decoder($metadata, $namespaces, $registry),
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
