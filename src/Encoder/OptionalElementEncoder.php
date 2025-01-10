<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Node\ElementList;
use Soap\Encoding\Xml\Writer\NilAttributeBuilder;
use Soap\Encoding\Xml\Writer\XsdTypeXmlElementWriter;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Xml\Xmlns\Xmlns;
use function count;
use function is_string;

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
        $isList = $meta->isList()->unwrapOr(false);

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
            static function (ElementList|Element|string $xml) use ($elementIso, $isList) : mixed {
                if ($xml === '') {
                    return null;
                }

                $parsedXml = match(true) {
                    $isList && is_string($xml) => ElementList::fromString('<list>'.$xml.'</list>'),
                    is_string($xml) => Element::fromString($xml),
                    default => $xml,
                };

                $documentElement = match (true) {
                    $parsedXml instanceof ElementList && count($parsedXml) === 1 => $parsedXml->elements()[0]->element(),
                    $parsedXml instanceof Element => $parsedXml->element(),
                    default => null
                };
                if ($documentElement && $documentElement->getAttributeNS(Xmlns::xsi()->value(), 'nil') === 'true') {
                    return null;
                }

                /** @var Iso<T|null, ElementList|Element|non-empty-string> $elementIso */
                return $elementIso->from($xml);
            }
        );
    }
}
