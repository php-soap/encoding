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
use function Psl\Dict\map_with_key;
use function VeeWee\Xml\Writer\Builder\children;
use function VeeWee\Xml\Writer\Builder\element;
use function VeeWee\Xml\Writer\Builder\namespace_attribute;
use function VeeWee\Xml\Writer\Builder\value as buildValue;

/**
 * @template T
 *
 * @implements XmlEncoder<string, array<array-key, mixed>>
 */
final class ApacheMapEncoder implements XmlEncoder
{
    /**
     * @param Context $context
     * @return Iso<string, array<array-key, mixed>>
     */
    public function iso(Context $context): Iso
    {
        return (new Iso(
            fn(array $value): string => $this->encodeArray($context, $value),
            fn(string $value): array => $this->decodeArray($context, $value),
        ));
    }

    /**
     * @param list<T> $data
     */
    private function encodeArray(Context $context, array $data): string
    {
        $type = $context->type;
        $anyContext = $context->withType(XsdType::any());

        return (new XsdTypeXmlElementWriter())(
            $context,
            children([
                namespace_attribute($type->getXmlNamespace(), $type->getXmlNamespaceName()),
                new XsiAttributeBuilder($context, XsiTypeDetector::detectFromValue($context, $data)),
                ...\Psl\Vec\map_with_key(
                    $data,
                    static fn (mixed $key, mixed $value): \Closure => element(
                        'item',
                        children([
                            element('key', children([
                                (new XsiAttributeBuilder($anyContext, XsiTypeDetector::detectFromValue($anyContext, $key))),
                                buildValue((new ScalarTypeEncoder())->iso($context)->to($key))
                            ])),
                            element('value', children([
                                (new XsiAttributeBuilder($anyContext, XsiTypeDetector::detectFromValue($anyContext, $value))),
                                buildValue((new ScalarTypeEncoder())->iso($context)->to($value))
                            ])),
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
