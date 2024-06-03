<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SoapEnc;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\Feature\ListAware;
use Soap\Encoding\Encoder\SimpleType\ScalarTypeEncoder;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\TypeInference\XsiTypeDetector;
use Soap\Encoding\Xml\Reader\ElementValueReader;
use Soap\Encoding\Xml\Writer\XsiAttributeBuilder;
use Soap\Encoding\Xml\XsdTypeXmlElementWriter;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\WsdlReader\Model\Definitions\EncodingStyle;
use Soap\Xml\Xmlns;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Xml\Dom\Document;
use function Psl\Vec\map;
use function VeeWee\Xml\Dom\Locator\Element\children as readChildren;
use function VeeWee\Xml\Writer\Builder\children;
use function VeeWee\Xml\Writer\Builder\element;
use function VeeWee\Xml\Writer\Builder\prefixed_attribute;
use function VeeWee\Xml\Writer\Builder\value as buildValue;
use function VeeWee\Xml\Writer\Builder\namespaced_attribute as buildNamespacedAttribute;

/**
 * @template T
 *
 * @implements XmlEncoder<string, list<T>>
 */
final class SoapArrayEncoder implements XmlEncoder, ListAware
{
    /**
     * @return Iso<string, list<T>>
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
        $meta = $type->getMeta();
        $itemNodeName = $meta->arrayNodeName()->unwrapOr('item');
        $itemType = $meta->arrayType()
            ->map(static fn (array $info): string => $info['itemType'])
            ->unwrapOr(XsiTypeDetector::detectFromValue(
                $context->withType(XsdType::any()),
                $data[0] ?? null
            ));

        return (new XsdTypeXmlElementWriter())(
            $context,
            children([
                new XsiAttributeBuilder(
                    $context,
                    XsiTypeDetector::detectFromValue($context, [])
                ),
                prefixed_attribute(
                    'SOAP-ENC',
                    'arrayType',
                    $itemType . '['.count($data).']'
                ),
                ...map(
                    $data,
                    static fn (mixed $value): \Closure => element(
                        $itemNodeName,
                        children([
                            (new XsiAttributeBuilder($context, $itemType)),
                            buildValue((new ScalarTypeEncoder())->iso($context)->to($value))
                        ])
                    )
                )
            ])
        );

    }

    private function decodeArray(Context $context, string $value): array
    {
        $document = Document::fromXmlString($value);
        $element = $document->locateDocumentElement();

        return readChildren($element)->reduce(
            /**
             * @param list<mixed> $list
             * @return list<mixed>
             */
            static function (array $list, \DOMElement $item) use ($context): array
            {
                $value = (new ElementValueReader())(
                    $context->withType(XsdType::any()),
                    new ScalarTypeEncoder(),
                    $item
                );

                return [...$list, $value];
            },
            [],
        );
    }
}
