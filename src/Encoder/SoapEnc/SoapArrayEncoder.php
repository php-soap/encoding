<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SoapEnc;

use Closure;
use DOMElement;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\Feature\ListAware;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\TypeInference\XsiTypeDetector;
use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Writer\XsdTypeXmlElementWriter;
use Soap\Encoding\Xml\Writer\XsiAttributeBuilder;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use VeeWee\Reflecta\Iso\Iso;
use function count;
use function Psl\Fun\lazy;
use function Psl\Vec\map;
use function VeeWee\Xml\Dom\Locator\Element\children as readChildren;
use function VeeWee\Xml\Writer\Builder\children;
use function VeeWee\Xml\Writer\Builder\prefixed_attribute;
use function VeeWee\Xml\Writer\Builder\raw as buildRaw;

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
        $arrayAccess = lazy(static fn (): SoapArrayAccess => SoapArrayAccess::forContext($context));

        return (new Iso(
            /**
             * @param list<mixed> $value
             * @return non-empty-string
             */
            fn (array $value): string => $this->encodeArray($context, $arrayAccess(), $value),
            /**
             * @param non-empty-string|Element $value
             * @return list<mixed>
             */
            fn (string|Element $value): array => $this->decodeArray(
                $context,
                $arrayAccess(),
                $value instanceof Element ? $value : Element::fromString($value)
            ),
        ));
    }


    /**
     * @param list<mixed> $data
     *
     * @return non-empty-string
     */
    private function encodeArray(Context $context, SoapArrayAccess $arrayAccess, array $data): string
    {
        $iso = $arrayAccess->itemEncoder->iso($arrayAccess->itemContext);

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
                                $arrayAccess->xsiType . '['.count($data).']'
                            ),
                        ]
                        : []
                ),
                ...map(
                    $data,
                    static fn (mixed $value): Closure => buildRaw((string) $iso->to($value))
                )
            ])
        );
    }

    /**
     * @return list<mixed>
     */
    private function decodeArray(Context $context, SoapArrayAccess $arrayAccess, Element $value): array
    {
        $element = $value->element();
        $iso = $arrayAccess->itemEncoder->iso($arrayAccess->itemContext);

        return readChildren($element)->reduce(
            /**
             * @param list<mixed> $list
             * @return list<mixed>
             */
            static function (array $list, DOMElement $item) use ($iso): array {
                /** @var mixed $value */
                $value = $iso->from(Element::fromDOMElement($item));

                return [...$list, $value];
            },
            [],
        );
    }
}
