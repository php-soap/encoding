<?php

declare(strict_types=1);

namespace Soap\Encoding\Test\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Decoder as SoapDecoder;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\EncoderRegistry;
use Soap\Engine\Decoder;
use Soap\EngineIntegrationTests\AbstractDecoderTest;
use Soap\EngineIntegrationTests\Type\ValidateResponse;
use Soap\Wsdl\Loader\StreamWrapperLoader;
use Soap\WsdlReader\Metadata\Wsdl1MetadataProvider;
use Soap\WsdlReader\Wsdl1Reader;

#[CoversClass(SoapDecoder::class)]
#[CoversClass(EncoderRegistry::class)]
#[CoversClass(Context::class)]
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
            $wsdlInfo->namespaces,
            EncoderRegistry::default()
                ->addClassMap('http://soapinterop.org/xsd', 'MappedValidateResponse', ValidateResponse::class)
        );
    }
}
