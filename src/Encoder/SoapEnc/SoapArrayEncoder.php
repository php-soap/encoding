<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SoapEnc;

use Closure;
use DOMElement;
use Generator;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\Feature\ListAware;
use Soap\Encoding\Encoder\SimpleType\ScalarTypeEncoder;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\TypeInference\XsiTypeDetector;
use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Reader\ElementValueReader;
use Soap\Encoding\Xml\Writer\XsdTypeXmlElementWriter;
use Soap\Encoding\Xml\Writer\XsiAttributeBuilder;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Parser\Xml\QnameParser;
use VeeWee\Reflecta\Iso\Iso;
use XMLWriter;
use function count;
use function Psl\Vec\map;
use function VeeWee\Xml\Dom\Locator\Element\children as readChildren;
use function VeeWee\Xml\Writer\Builder\children;
use function VeeWee\Xml\Writer\Builder\element;
use function VeeWee\Xml\Writer\Builder\namespaced_element;
use function VeeWee\Xml\Writer\Builder\prefixed_attribute;
use function VeeWee\Xml\Writer\Builder\value as buildValue;

/**
 * @implements XmlEncoder<list<mixed>, non-empty-string>
 */
final class SoapArrayEncoder implements ListAware, XmlEncoder
{
    /**
     * @return Iso<list<mixed>, non-empty-string>
     */
    public function iso(Context $context): Iso
    {
        return (new Iso(
            /**
             * @param list<mixed> $value
             * @return non-empty-string
             */
            fn (array $value): string => $this->encodeArray($context, $value),
            /**
             * @param non-empty-string|Element $value
             * @return list<mixed>
             */
            fn (string|Element $value): array => $this->decodeArray(
                $context,
                $value instanceof Element ? $value : Element::fromString($value)
            ),
        ));
    }

    /**
     * @param list<mixed> $data
     *
     * @return non-empty-string
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

    /**
     * @psalm-param mixed $value
     * @return Closure(XMLWriter): Generator<bool>
     */
    private function itemElement(Context $context, ?string $itemNodeName, string $itemType, mixed $value): Closure
    {
        $buildValue = buildValue(ScalarTypeEncoder::default()->iso($context)->to($value));

        if ($context->bindingUse === BindingUse::ENCODED || $itemNodeName !== null) {
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

    /**
     * @return list<mixed>
     */
    private function decodeArray(Context $context, Element $value): array
    {
        $element = $value->element();

        return readChildren($element)->reduce(
            /**
             * @param list<mixed> $list
             * @return list<mixed>
             */
            static function (array $list, DOMElement $item) use ($context): array {
                /** @psalm-var mixed $value */
                $value = (new ElementValueReader())(
                    $context->withType(XsdType::any()),
                    ScalarTypeEncoder::default(),
                    $item
                );

                return [...$list, $value];
            },
            [],
        );
    }
}
