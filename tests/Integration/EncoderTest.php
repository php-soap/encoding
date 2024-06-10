<?php

declare(strict_types=1);

namespace Soap\Encoding\Test\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Encoder as SoapEncoder;
use Soap\Encoding\EncoderRegistry;
use Soap\Engine\Encoder;
use Soap\EngineIntegrationTests\AbstractEncoderTest;
use Soap\EngineIntegrationTests\Type\ValidateRequest;
use Soap\Wsdl\Loader\StreamWrapperLoader;
use Soap\WsdlReader\Metadata\Wsdl1MetadataProvider;
use Soap\WsdlReader\Wsdl1Reader;

#[CoversClass(SoapEncoder::class)]
#[CoversClass(EncoderRegistry::class)]
#[CoversClass(SoapEncoder\Context::class)]
final class EncoderTest extends AbstractEncoderTest
{
    private SoapEncoder $encoder;

    protected function getEncoder(): Encoder
    {
        return $this->encoder;
    }

    protected function configureForWsdl(string $wsdl)
    {
        $loader = new StreamWrapperLoader();
        $wsdlInfo = (new Wsdl1Reader($loader))($wsdl);
        $metadataProvider = new Wsdl1MetadataProvider($wsdlInfo);
        $metadata = $metadataProvider->getMetadata();

        $this->encoder = new SoapEncoder(
            $metadata,
            $wsdlInfo->namespaces,
            EncoderRegistry::default()
                ->addClassMap('http://soapinterop.org/xsd', 'MappedValidateRequest', ValidateRequest::class)
        );
    }
}
