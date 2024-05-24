<?php

declare(strict_types=1);

namespace Soap\Encoding\Test\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Decoder as SoapDecoder;
use Soap\Encoding\Encoder;
use Soap\Encoding\EncoderRegistry;
use Soap\Engine\Decoder;
use Soap\EngineIntegrationTests\AbstractDecoderTest;
use Soap\EngineIntegrationTests\Type\ValidateRequest;
use Soap\Wsdl\Loader\StreamWrapperLoader;
use Soap\WsdlReader\Metadata\Wsdl1MetadataProvider;
use Soap\WsdlReader\Wsdl1Reader;

#[CoversClass(SoapDecoder::class)]
final class DecoderTest extends AbstractDecoderTest
{
    private SoapDecoder $decoder;

    protected function getDecoder(): Decoder
    {
        return $this->decoder;
    }

    protected function configureForWsdl(string $wsdl)
    {
        $loader = new StreamWrapperLoader();
        $wsdlInfo = (new Wsdl1Reader($loader))($wsdl);
        $metadataProvider = new Wsdl1MetadataProvider($wsdlInfo);
        $metadata = $metadataProvider->getMetadata();

        $this->decoder = new SoapDecoder(
            $metadata,
            EncoderRegistry::default()
                ->addClassMap('http://soapinterop.org/xsd', 'MappedValidateRequest', ValidateRequest::class)
        );
    }
}
