<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SoapEnc;

use Closure;
use DOMElement;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\SimpleType\ScalarTypeEncoder;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\TypeInference\XsiTypeDetector;
use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Reader\ElementValueReader;
use Soap\Encoding\Xml\Writer\XsdTypeXmlElementWriter;
use Soap\Encoding\Xml\Writer\XsiAttributeBuilder;
use Soap\Engine\Metadata\Model\XsdType;
use VeeWee\Reflecta\Iso\Iso;
use function Psl\Dict\merge;
use function Psl\Type\string;
use function VeeWee\Xml\Dom\Assert\assert_element;
use function VeeWee\Xml\Dom\Locator\Element\children as readChildren;
use function VeeWee\Xml\Writer\Builder\children as buildChildren;
use function VeeWee\Xml\Writer\Builder\element;
use function VeeWee\Xml\Writer\Builder\value as buildValue;

/**
 * @implements XmlEncoder<array<array-key, mixed>, non-empty-string>
 */
final class ApacheMapEncoder implements XmlEncoder
{
    /**
     * @return Iso<array<array-key, mixed>, non-empty-string>
     */
    public function iso(Context $context): Iso
    {
        return (new Iso(
            /**
             * @return non-empty-string
             */
            fn (array $value): string => $this->encodeArray($context, $value),
            /**
             * @param non-empty-string|Element $value
             */
            fn (string|Element $value): array => $this->decodeArray(
                $context,
                $value instanceof Element ? $value : Element::fromString($value)
            ),
        ));
    }

    /**
     * @return non-empty-string
     */
    private function encodeArray(Context $context, array $data): string
    {
        $anyContext = $context->withType(XsdType::any());

        return (new XsdTypeXmlElementWriter())(
            $context,
            buildChildren([
                new XsiAttributeBuilder($context, XsiTypeDetector::detectFromValue($context, $data)),
                ...\Psl\Vec\map_with_key(
                    $data,
                    static fn (mixed $key, mixed $value): Closure => element(
                        'item',
                        buildChildren([
                            element('key', buildChildren([
                                (new XsiAttributeBuilder($anyContext, XsiTypeDetector::detectFromValue($anyContext, $key))),
                                buildValue(ScalarTypeEncoder::default()->iso($context)->to($key))
                            ])),
                            element('value', buildChildren([
                                (new XsiAttributeBuilder($anyContext, XsiTypeDetector::detectFromValue($anyContext, $value))),
                                buildValue(ScalarTypeEncoder::default()->iso($context)->to($value))
                            ])),
                        ]),
                    )
                )
            ])
        );
    }

    private function decodeArray(Context $context, Element $value): array
    {
        $element = $value->element();
        $xpath = $value->document()->xpath();

        return readChildren($element)->reduce(
            static function (array $map, DOMElement $item) use ($context, $xpath): array {
                $key = $xpath->evaluate('string(./key)', string(), $item);
                /** @psalm-var mixed $value */
                $value = (new ElementValueReader())(
                    $context->withType(XsdType::any()),
                    ScalarTypeEncoder::default(),
                    assert_element($xpath->querySingle('./value', $item))
                );

                return merge($map, [
                    $key => $value,
                ]);
            },
            [],
        );
    }
}
