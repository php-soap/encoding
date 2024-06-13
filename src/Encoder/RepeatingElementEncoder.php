<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Node\ElementList;
use Soap\Engine\Metadata\Model\TypeMeta;
use VeeWee\Reflecta\Iso\Iso;
use function Psl\Str\join;
use function Psl\Vec\map;

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
            static function (Element|ElementList|string $xml) use ($innerIso): iterable {

                $elements = match (true) {
                    $xml instanceof Element => [$xml],
                    $xml instanceof ElementList => $xml->elements(),
                    default => ElementList::fromString('<list>'.$xml.'</list>')->elements()
                };

                /** @var Iso<T|null, Element|non-empty-string> $innerIso */
                return map(
                    $elements,
                    static fn (Element $element): mixed => $innerIso->from($element)
                );
            }
        );
    }
}
