<?php declare(strict_types=1);

namespace Soap\Encoding\Encoder\Method;

use Closure;
use Generator;
use Soap\Encoding\Xml\Reader\OperationReader;
use Soap\Encoding\Xml\Writer\OperationBuilder;
use Soap\Encoding\Xml\Writer\ParameterBuilder;
use Soap\Encoding\Xml\Writer\SoapEnvelopeWriter;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Model\Definitions\EncodingStyle;
use Soap\WsdlReader\Model\Definitions\SoapVersion;
use VeeWee\Reflecta\Iso\Iso;
use XMLWriter;
use function Psl\invariant;
use function Psl\Vec\map;

/**
 * @template-implements SoapMethodEncoder<mixed, string>
 */
final class ResponseEncoder implements SoapMethodEncoder
{
    /**
     * @return Iso<mixed, string>
     */
    public function iso(MethodContext $context): Iso
    {
        $meta = $context->method->getMeta();
        $context = $context->withBindingUse(
            $meta->outputBindingUsage()->map(BindingUse::from(...))->unwrapOr(BindingUse::LITERAL)
        );

        /** @var Iso<list<mixed>, string> */
        return new Iso(
            /**
             * @param list<mixed> $arguments
             */
            fn (array $arguments): string => $this->encode($context, $arguments),
            /**
             * @return list<mixed>
             */
            fn (string $xml): array => $this->decode($context, $xml),
        );
    }

    /**
     * @param list<mixed> $arguments
     */
    private function encode(MethodContext $context, array $arguments): string
    {
        $method = $context->method;
        $meta = $method->getMeta();

        if ($meta->isOneWay()->unwrapOr(false)) {
            return '';
        }

        $soapVersion = $meta->soapVersion()->map(SoapVersion::from(...))->unwrapOr(SoapVersion::SOAP_12);
        $encodingStyle = $meta->outputEncodingStyle()->map(EncodingStyle::from(...));

        $returnType = $method->getReturnType();
        $typeContext = $context->createXmlEncoderContextForType($returnType);

        $responseParams = map(
            $arguments,
            /**
             * @return Closure(XMLWriter): Generator<bool>
             */
            static fn (mixed $argument): Closure => (new ParameterBuilder($meta, $typeContext, $argument))(...),
        );

        $operation = new OperationBuilder($meta, $context->namespaces, $responseParams);
        $writeEnvelope = new SoapEnvelopeWriter($soapVersion, $context->bindingUse, $encodingStyle, $operation(...));

        return $writeEnvelope() . PHP_EOL;
    }

    /**
     * @return list<mixed>
     */
    private function decode(MethodContext $context, string $xml): array
    {
        $method = $context->method;
        $meta = $method->getMeta();

        if ($meta->isOneWay()->unwrapOr(false)) {
            return [];
        }

        $returnType = $method->getReturnType();
        $typeContext = $context->createXmlEncoderContextForType($returnType);
        $decoder = $context->registry->detectEncoderForContext($typeContext);
        $iso = $decoder->iso($typeContext);

        // The SoapResponse only contains the payload of the response (with no headers).
        // It can be parsed directly as XML.
        invariant($xml !== '', 'Expected a non-empty response payload. Received an empty HTTP response');
        $parts = (new OperationReader($meta))($xml)->elements();

        return map(
            $parts,
            $iso->from(...)
        );
    }
}
