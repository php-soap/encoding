<?php
declare(strict_types=1);

namespace Soap\Encoding;

use Soap\Encoding\Encoder\Method\MethodContext;
use Soap\Encoding\Encoder\Method\RequestEncoder;
use Soap\Engine\Encoder as SoapEncoder;
use Soap\Engine\HttpBinding\SoapRequest;
use Soap\Engine\Metadata\Metadata;
use Soap\WsdlReader\Model\Definitions\Namespaces;
use Soap\WsdlReader\Model\Definitions\SoapVersion;
use function Psl\Type\mixed_vec;

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
        $methodContext = new MethodContext($methodInfo, $this->metadata, $this->registry, $this->namespaces);
        $soapVersion = $meta->soapVersion()->map(SoapVersion::from(...))->unwrapOr(SoapVersion::SOAP_12);
        $iso = (new RequestEncoder())->iso($methodContext);

        return new SoapRequest(
            $iso->to(mixed_vec()->assert($arguments)),
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
