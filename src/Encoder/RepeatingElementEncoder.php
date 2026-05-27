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
 * @implements XmlEncoder<iterable<array-key, T>|null, iterable<array-key, T>, string, Element|ElementList|string>
 */
final class RepeatingElementEncoder implements Feature\ElementAware, Feature\ListAware, XmlEncoder
{
    /**
     * @param XmlEncoder<T, T, string|null, Element|ElementList|string|null> $typeEncoder
     */
    public function __construct(
        private readonly XmlEncoder $typeEncoder
    ) {
    }

    /**
     * @return Iso<iterable<array-key, T>|null, iterable<array-key, T>, string, Element|ElementList|string>
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
             * @param iterable<array-key, T>|null $raw
             */
            static function (iterable|null $raw) use ($innerIso): string {
                return join(
                    map(
                        $raw ?? [],
                        /**
                         * @param T $item
                         *
                         * The inner XML output slot is `string|null` because it mirrors the shared aggregate
                         * encoder type, but element encoders always produce a string in the to() direction;
                         * the cast only collapses type-level nullability inherited from the aggregate.
                         */
                        static fn (mixed $item): string => (string) $innerIso->to($item)
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

                /** @var Iso<T|null, T|null, Element|non-empty-string, Element|non-empty-string> $innerIso */
                return map(
                    $elements,
                    static fn (Element $element): mixed => $innerIso->from($element)
                );
            }
        );
    }
}
