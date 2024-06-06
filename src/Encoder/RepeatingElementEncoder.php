<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use DOMElement;
use Soap\Engine\Metadata\Model\TypeMeta;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Xml\Dom\Document;
use function Psl\Str\join;
use function Psl\Vec\map;
use function VeeWee\Xml\Dom\Locator\Element\children as readChildren;

/**
 * @template T
 * @implements XmlEncoder<iterable<array-key, T>, string>
 */
final class RepeatingElementEncoder implements Feature\ListAware, XmlEncoder
{
    /**
     * @param XmlEncoder<T, string> $typeEncoder
     */
    public function __construct(
        private readonly XmlEncoder $typeEncoder
    ) {
    }

    /**
     * @return Iso<iterable<array-key, T>, string>
     */
    public function iso(Context $context): Iso
    {
        $type = $context->type;
        $innerIso = $this->typeEncoder->iso(
            $context->withType(
                $type->withMeta(static fn (TypeMeta $meta): TypeMeta => $meta->withIsList(false))
            )
        );


        return new Iso(
            /**
             * @param iterable<array-key, T> $raw
             */
            static function (iterable $raw) use ($innerIso): string {
                return join(
                    map(
                        $raw,
                        /**
                         * @param T $item
                         */
                        static fn (mixed $item): string => $innerIso->to($item)
                    ),
                    ''
                );
            },
            /**
             * @return iterable<array-key, T>
             */
            static function (string $xml) use ($innerIso): iterable {
                $doc = Document::fromXmlString('<list>'.$xml.'</list>');

                return readChildren($doc->locateDocumentElement())->map(
                    static fn (DOMElement $element): mixed => $innerIso->from($doc->stringifyNode($element))
                );
            }
        );
    }
}
