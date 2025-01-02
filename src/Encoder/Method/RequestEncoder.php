<?php declare(strict_types=1);

namespace Soap\Encoding\Encoder\Method;

use Closure;
use Generator;
use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Reader\OperationReader;
use Soap\Encoding\Xml\Writer\OperationBuilder;
use Soap\Encoding\Xml\Writer\ParameterBuilder;
use Soap\Encoding\Xml\Writer\SoapEnvelopeWriter;
use Soap\Engine\Metadata\Model\Parameter;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Model\Definitions\EncodingStyle;
use Soap\WsdlReader\Model\Definitions\SoapVersion;
use VeeWee\Reflecta\Iso\Iso;
use XMLWriter;
use function Psl\Vec\map_with_key;
use function VeeWee\Reflecta\Lens\index;

/**
 * @template-implements SoapMethodEncoder<list<mixed>, non-empty-string>
 */
final class RequestEncoder implements SoapMethodEncoder
{
    /**
     * @return Iso<list<mixed>, non-empty-string>
     */
    public function iso(MethodContext $context): Iso
    {
        $meta = $context->method->getMeta();
        $context = $context->withBindingUse(
            $meta->inputBindingUsage()->map(BindingUse::from(...))->unwrapOr(BindingUse::LITERAL)
        );

        /** @var Iso<list<mixed>, non-empty-string> */
        return new Iso(
            /**
             * @param list<mixed> $arguments
             * @return non-empty-string
             */
            fn (array $arguments): string => $this->encode($context, $arguments),
            /**
             * @param non-empty-string $xml
             * @return list<mixed>
             */
            fn (string $xml): array => $this->decode($context, $xml),
        );
    }


    /**
     * @param list<mixed> $arguments
     * @return non-empty-string
     */
    private function encode(MethodContext $context, array $arguments): string
    {
        $method = $context->method;
        $meta = $method->getMeta();
        $soapVersion = $meta->soapVersion()->map(SoapVersion::from(...))->unwrapOr(SoapVersion::SOAP_12);
        $encodingStyle = $meta->inputEncodingStyle()->map(EncodingStyle::from(...));

        $requestParams = map_with_key(
            $method->getParameters(),
            /**
             * @return Closure(XMLWriter): Generator<bool>
             */
            static function (int $index, Parameter $parameter) use ($meta, $context, $arguments): Closure {
                $type = $parameter->getType();
                $typeContext = $context->createXmlEncoderContextForType($type);
                /** @var mixed $value */
                $value = index($index)->get($arguments);

                return (new ParameterBuilder($meta, $typeContext, $value))(...);
            }
        );

        $operation = new OperationBuilder($meta, $context->namespaces, $requestParams);
        $writeEnvelope = new SoapEnvelopeWriter($soapVersion, $context->bindingUse, $encodingStyle, $operation(...));

        return $writeEnvelope() . PHP_EOL;
    }


    /**
     * @param non-empty-string $xml
     * @return list<mixed>
     */
    private function decode(MethodContext $context, string $xml): array
    {
        $method = $context->method;
        $meta = $method->getMeta();
        $parts = (new OperationReader($meta))($xml)->elements();

        return map_with_key(
            $method->getParameters(),
            static function (int $index, Parameter $parameter) use ($context, $parts) : mixed {
                $type = $parameter->getType();
                $typeContext = $context->createXmlEncoderContextForType($type);
                $decoder = $context->registry->detectEncoderForContext($typeContext);
                $iso = $decoder->iso($typeContext);

                /** @var Element $value */
                $value = index($index)->get($parts);

                /** @var mixed */
                return $iso->from($value);
            }
        );
    }
}
