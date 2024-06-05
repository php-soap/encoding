<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SoapEnc;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\SimpleType\ScalarTypeEncoder;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\TypeInference\XsiTypeDetector;
use Soap\Encoding\Xml\Reader\ElementValueReader;
use Soap\Encoding\Xml\Writer\XsiAttributeBuilder;
use Soap\Encoding\Xml\Writer\XsdTypeXmlElementWriter;
use Soap\Engine\Metadata\Model\XsdType;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Xml\Dom\Document;
use function Psl\Dict\merge;
use function Psl\Type\string;
use function VeeWee\Xml\Dom\Assert\assert_element;
use function VeeWee\Xml\Dom\Locator\Element\children as readChildren;
use function VeeWee\Xml\Writer\Builder\children as buildChildren;
use function VeeWee\Xml\Writer\Builder\element;
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

    private function encodeArray(Context $context, array $data): string
    {
        $anyContext = $context->withType(XsdType::any());

        return (new XsdTypeXmlElementWriter())(
            $context,
            buildChildren([
                new XsiAttributeBuilder($context, XsiTypeDetector::detectFromValue($context, $data)),
                ...\Psl\Vec\map_with_key(
                    $data,
                    static fn (mixed $key, mixed $value): \Closure => element(
                        'item',
                        buildChildren([
                            element('key', buildChildren([
                                (new XsiAttributeBuilder($anyContext, XsiTypeDetector::detectFromValue($anyContext, $key))),
                                buildValue(ScalarTypeEncoder::static()->iso($context)->to($key))
                            ])),
                            element('value', buildChildren([
                                (new XsiAttributeBuilder($anyContext, XsiTypeDetector::detectFromValue($anyContext, $value))),
                                buildValue(ScalarTypeEncoder::static()->iso($context)->to($value))
                            ])),
                        ]),
                    )
                )
            ])
        );
    }

    private function decodeArray(Context $context, string $value): array
    {
        $document = Document::fromXmlString($value);
        $xpath = $document->xpath();
        $element = $document->locateDocumentElement();

        return readChildren($element)->reduce(
            static function (array $map, \DOMElement $item) use ($context, $xpath): array
            {
                $key = $xpath->evaluate('string(./key)', string(), $item);
                $value = (new ElementValueReader())(
                    $context->withType(XsdType::any()),
                    ScalarTypeEncoder::static(),
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
