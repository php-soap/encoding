<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SoapEnc;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\SimpleType\ScalarTypeEncoder;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\TypeInference\XsiTypeDetector;
use Soap\Encoding\Xml\Writer\XsiAttributeBuilder;
use Soap\Encoding\Xml\XsdTypeXmlElementWriter;
use Soap\Engine\Metadata\Model\XsdType;
use VeeWee\Reflecta\Iso\Iso;
use function VeeWee\Xml\Writer\Builder\children;
use function VeeWee\Xml\Writer\Builder\element;
use function VeeWee\Xml\Writer\Builder\namespace_attribute;
use function VeeWee\Xml\Writer\Builder\value as buildValue;

/**
 * @implements XmlEncoder<string, object>
 */
final class SoapObjectEncoder implements XmlEncoder
{
    /**
     * @param Context $context
     * @return Iso<string, object>
     */
    public function iso(Context $context): Iso
    {
        return (new Iso(
            fn(object $value): string => $this->encodeArray($context, $value),
            fn(string $value): object => $this->decodeArray($context, $value),
        ));
    }

    private function encodeArray(Context $context, object $data): string
    {
        $type = $context->type;
        $anyContext = $context->withType(XsdType::any());

        return (new XsdTypeXmlElementWriter())(
            $context,
            children([
                namespace_attribute($type->getXmlNamespace(), $type->getXmlNamespaceName()),
                new XsiAttributeBuilder($context, XsiTypeDetector::detectFromValue($context, $data)),
                ...\Psl\Vec\map_with_key(
                    (array) $data,
                    static fn (mixed $key, mixed $value): \Closure => element(
                        $key,
                        children([
                            (new XsiAttributeBuilder($anyContext, XsiTypeDetector::detectFromValue($anyContext, $value))),
                            buildValue((new ScalarTypeEncoder())->iso($context)->to($value))
                        ]),
                    )
                )
            ])
        );
    }

    private function decodeArray(Context $context, string $part): array
    {
        throw new \RuntimeException('Not implemented yet!');
    }
}