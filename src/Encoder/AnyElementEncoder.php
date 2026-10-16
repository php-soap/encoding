<?php declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Node\ElementList;
use Soap\Encoding\Xml\Node\ElementLookup;
use Soap\Engine\Metadata\Model\Property;
use Soap\Engine\Metadata\Model\Type;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Reflecta\Lens\Lens;
use function is_array;
use function is_string;
use function Psl\Dict\diff_by_key;
use function Psl\Iter\first;
use function Psl\Iter\reduce;
use function Psl\Str\join;
use function Psl\Type\string;
use function Psl\Type\vec;

/**
 * @implements XmlEncoder<array|string|null, array<array-key, string>|string|null, string, ElementList|string>
 *
 * @template-implements Feature\ProvidesObjectDecoderLens<ElementLookup, ElementLookup, ElementList, ElementList>
 */
final class AnyElementEncoder implements Feature\ListAware, Feature\OptionalAware, Feature\ProvidesObjectDecoderLens, XmlEncoder
{
    /**
     * This lens will be used to decode XML into an 'any' property.
     * It will contain all the XML tags available in the object that is surrounding the 'any' property.
     * Properties that are already known by the object, will be omitted.
     *
     * @return Lens<ElementLookup, ElementLookup, ElementList, ElementList>
     */
    public static function createObjectDecoderLens(Type $parentType, Property $currentProperty): Lens
    {
        $omittedKeys = array_flip(reduce(
            $parentType->getProperties(),
            static fn (array $omit, Property $property): array => [
                ...$omit,
                ...($property->getName() !== $currentProperty->getName() ? [$property->getName()] : []),
            ],
            []
        ));

        /** @var Lens<ElementLookup, ElementLookup, ElementList, ElementList> */
        return Lens::readonly(
            static function (ElementLookup $lookup) use ($omittedKeys): ElementList {
                /** @var array<string, Element|ElementList> $children */
                $children = diff_by_key($lookup->children(), $omittedKeys);

                return ElementList::fromLookupArray($children);
            }
        );
    }

    /**
     * @return Iso<array|string|null, array<array-key, string>|string|null, string, ElementList|string>
     */
    public function iso(Context $context): Iso
    {
        $meta = $context->type->getMeta();
        $isNullable = $meta->isNullable()->unwrapOr(false);
        $isList = $meta->isList()->unwrapOr(false);

        return new Iso(
            static fn (string|array|null $raw): string => match (true) {
                is_string($raw) => $raw,
                is_array($raw) => join(vec(string())->assert($raw), ''),
                default => '',
            },
            /**
             * @psalm-suppress DocblockTypeContradiction - Psalm gets confused about the return type of first() in default case.
             * @psalm-return null|array<array-key, string>|string
             */
            static fn (ElementList|string $xml): mixed => match(true) {
                is_string($xml) => $xml,
                $isList && !$xml->hasElements() => [],
                $isNullable && !$xml->hasElements() => null,
                $isList => $xml->traverse(static fn (Element $element) => $element->value()),
                default => first($xml->elements())?->value(),
            }
        );
    }
}
