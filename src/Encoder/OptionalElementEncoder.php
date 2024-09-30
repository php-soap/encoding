<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Writer\NilAttributeBuilder;
use Soap\Encoding\Xml\Writer\XsdTypeXmlElementWriter;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Xml\Xmlns\Xmlns;

/**
 * @template T of mixed
 * @implements XmlEncoder<T, string>
 */
final class OptionalElementEncoder implements Feature\ElementAware, Feature\OptionalAware, XmlEncoder
{
    /**
     * @param XmlEncoder<T, string> $elementEncoder
     */
    public function __construct(
        private readonly XmlEncoder $elementEncoder
    ) {
    }

    /**
     * @return Iso<T, string>
     */
    public function iso(Context $context): Iso
    {
        $type = $context->type;
        $meta = $type->getMeta();
        $elementIso = $this->elementEncoder->iso($context);

        if (!$meta->isNullable()->unwrapOr(false)) {
            return $elementIso;
        }

        return new Iso(
            /**
             * @param T|null $raw
             */
            static fn (mixed $raw): string => match (true) {
                $raw === null && $meta->isNil()->unwrapOr(false) => (new XsdTypeXmlElementWriter())($context, new NilAttributeBuilder()),
                $raw === null => '',
                default => $elementIso->to($raw),
            },
            /**
             * @return T|null
             */
            static function (Element|string $xml) use ($elementIso) : mixed {
                if ($xml === '') {
                    return null;
                }

                $documentElement = ($xml instanceof Element ? $xml : Element::fromString($xml))->element();
                if ($documentElement->getAttributeNS(Xmlns::xsi()->value(), 'nil') === 'true') {
                    return null;
                }

                /** @var Iso<T|null, Element|non-empty-string> $elementIso */
                return $elementIso->from($xml);
            }
        );
    }
}
