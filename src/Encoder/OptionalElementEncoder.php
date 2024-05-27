<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\Xml\Writer\NilAttributeBuilder;
use Soap\Encoding\Xml\XsdTypeXmlElementWriter;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Xml\Dom\Document;
use VeeWee\Xml\Xmlns\Xmlns;

/**
 * @template T of mixed
 * @implements XmlEncoder<string, T>
 */
final class OptionalElementEncoder implements XmlEncoder
{
    /**
     * @param XmlEncoder<string, T> $elementEncoder
     */
    public function __construct(
        private readonly XmlEncoder $elementEncoder
    ) {
    }

    /**
     * @return Iso<string, T>
     */
    public function iso(Context $context): Iso
    {
        $type = $context->type;
        $meta = $type->getMeta();
        $elementIso = $this->elementEncoder->iso($context);

        $isNullable = $meta->isNullable()->unwrapOr(false);
        if (!$isNullable) {
            return $elementIso;
        }

        $isNillable = $meta->isNil()->unwrapOr(false);
        $elementIso = $this->elementEncoder->iso($context);

        return new Iso(
            static fn (mixed $raw): string => match (true) {
                $raw === null && $isNillable => (new XsdTypeXmlElementWriter())($context, new NilAttributeBuilder()),
                $raw === null => '',
                default => $elementIso->to($raw),
            },
            static function (string $xml) use ($elementIso) : mixed {
                if ($xml === '') {
                    return null;
                }

                $documentElement = Document::fromXmlString($xml)->locateDocumentElement();
                if ($documentElement->getAttributeNS(Xmlns::xsi()->value(), 'nil') === 'true') {
                    return null;
                }

                return $elementIso->from($xml);
            }
        );
    }
}
