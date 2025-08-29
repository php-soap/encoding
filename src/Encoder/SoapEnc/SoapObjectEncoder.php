<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SoapEnc;

use Closure;
use DOMElement;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\SimpleType\ScalarTypeEncoder;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Reader\ElementValueReader;
use Soap\Encoding\Xml\Writer\XsdTypeXmlElementWriter;
use Soap\Encoding\Xml\Writer\XsiAttributeBuilder;
use Soap\Engine\Metadata\Model\XsdType;
use VeeWee\Reflecta\Iso\Iso;
use function Psl\Dict\merge;
use function VeeWee\Xml\Dom\Locator\Element\children as readChildren;
use function VeeWee\Xml\Writer\Builder\children;
use function VeeWee\Xml\Writer\Builder\element;
use function VeeWee\Xml\Writer\Builder\value as buildValue;

/**
 * @implements XmlEncoder<object, non-empty-string>
 */
final class SoapObjectEncoder implements XmlEncoder
{
    /**
     * @return Iso<object, non-empty-string>
     */
    public function iso(Context $context): Iso
    {
        return (new Iso(
            /**
             * @return non-empty-string
             */
            fn (object $value): string => $this->encodeArray($context, $value),
            /**
             * @param non-empty-string|Element $value
             */
            fn (string|Element $value): object => $this->decodeArray(
                $context,
                $value instanceof Element ? $value : Element::fromString($value)
            ),
        ));
    }

    /**
     * @return non-empty-string
     */
    private function encodeArray(Context $context, object $data): string
    {
        $anyContext = $context->withType(XsdType::any());

        return (new XsdTypeXmlElementWriter())(
            $context,
            children([
                new XsiAttributeBuilder($context, XsiAttributeBuilder::resolveXsiTypeForValue($context, $data)),
                ...\Psl\Vec\map_with_key(
                    (array) $data,
                    static fn (mixed $key, mixed $value): Closure => element(
                        (string) $key,
                        children([
                            (new XsiAttributeBuilder($anyContext, XsiAttributeBuilder::resolveXsiTypeForValue($anyContext, $value))),
                            buildValue(ScalarTypeEncoder::default()->iso($context)->to($value))
                        ]),
                    )
                )
            ])
        );
    }

    private function decodeArray(Context $context, Element $value): object
    {
        $element = $value->element();

        return (object) readChildren($element)->reduce(
            static function (array $map, DOMElement $item) use ($context): array {
                $key = $item->localName ?? 'unkown';
                /** @psalm-var mixed $value */
                $value = (new ElementValueReader())(
                    $context->withType(XsdType::any()),
                    ScalarTypeEncoder::default(),
                    $item
                );

                return merge($map, [
                    $key => $value,
                ]);
            },
            [],
        );
    }
}
