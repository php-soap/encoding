<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SoapEnc;

use Closure;
use DOMElement;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\Feature\ListAware;
use Soap\Encoding\Encoder\SimpleType\ScalarTypeEncoder;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\TypeInference\XsiTypeDetector;
use Soap\Encoding\Xml\Reader\ElementValueReader;
use Soap\Encoding\Xml\Writer\XsdTypeXmlElementWriter;
use Soap\Encoding\Xml\Writer\XsiAttributeBuilder;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Parser\Xml\QnameParser;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Xml\Dom\Document;
use function Psl\Vec\map;
use function VeeWee\Xml\Dom\Locator\Element\children as readChildren;
use function VeeWee\Xml\Writer\Builder\children;
use function VeeWee\Xml\Writer\Builder\element;
use function VeeWee\Xml\Writer\Builder\namespaced_element;
use function VeeWee\Xml\Writer\Builder\prefixed_attribute;
use function VeeWee\Xml\Writer\Builder\value as buildValue;

/**
 * @template T
 *
 * @implements XmlEncoder<string, list<T>>
 */
final class SoapArrayEncoder implements ListAware, XmlEncoder
{
    /**
     * @return Iso<string, list<T>>
     */
    public function iso(Context $context): Iso
    {
        return (new Iso(
            fn (array $value): string => $this->encodeArray($context, $value),
            fn (string $value): array => $this->decodeArray($context, $value),
        ));
    }

    /**
     * @param list<T> $data
     */
    private function encodeArray(Context $context, array $data): string
    {
        $type = $context->type;
        $meta = $type->getMeta();
        $itemNodeName = $meta->arrayNodeName()->unwrapOr(null);
        $itemType = $meta->arrayType()
            ->map(static fn (array $info): string => $info['itemType'])
            ->unwrapOr(XsiTypeDetector::detectFromValue(
                $context->withType(XsdType::any()),
                $data[0] ?? null
            ));

        return (new XsdTypeXmlElementWriter())(
            $context,
            children([
                ...(
                    $context->bindingUse === BindingUse::ENCODED
                        ? [
                            new XsiAttributeBuilder(
                                $context,
                                XsiTypeDetector::detectFromValue($context, [])
                            ),
                            prefixed_attribute(
                                'SOAP-ENC',
                                'arrayType',
                                $itemType . '['.count($data).']'
                            ),
                        ]
                        : []
                ),
                ...map(
                    $data,
                    fn (mixed $value): Closure => $this->itemElement($context, $itemNodeName, $itemType, $value)
                )
            ])
        );
    }

    private function itemElement(Context $context, ?string $itemNodeName, string $itemType, mixed $value): Closure
    {
        $buildValue = buildValue(ScalarTypeEncoder::static()->iso($context)->to($value));

        if ($context->bindingUse === BindingUse::ENCODED || $itemNodeName) {
            return element(
                $itemNodeName ?? 'item',
                children([
                    (new XsiAttributeBuilder($context, $itemType)),
                    $buildValue
                ])
            );
        }

        [$prefix, $localName] = (new QnameParser())($itemType);

        return namespaced_element(
            $context->namespaces->lookupNamespaceFromName($prefix)->unwrap(),
            $prefix,
            $localName,
            $buildValue
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
            static function (array $list, DOMElement $item) use ($context): array {
                $value = (new ElementValueReader())(
                    $context->withType(XsdType::any()),
                    ScalarTypeEncoder::static(),
                    $item
                );

                return [...$list, $value];
            },
            [],
        );
    }
}
