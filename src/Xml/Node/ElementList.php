<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Node;

use Closure;
use DOMElement;
use Soap\Encoding\Xml\Reader\DocumentToLookupArrayReader;
use Stringable;
use VeeWee\Xml\Dom\Document;
use function Psl\Iter\reduce;
use function Psl\Vec\map;
use function VeeWee\Xml\Dom\Locator\Element\children as readChildren;

/**
 * @psalm-import-type LookupArray from DocumentToLookupArrayReader
 */
final class ElementList implements Stringable
{
    /** @var list<Element> */
    private array $elements;

    /**
     * @no-named-arguments
     */
    public function __construct(Element ...$elements)
    {
        $this->elements = $elements;
    }

    /**
     * Can be used to parse a nested array structure to a full flattened ElementList.
     *
     * @see \Soap\Encoding\Xml\Reader\DocumentToLookupArrayReader::__invoke
     *
     * @param LookupArray $data
     */
    public static function fromLookupArray(array $data): self
    {
        return new self(
            ...reduce(
                $data,
                /**
                 * @param list<Element> $elements
                 *
                 * @return list<Element>
                 */
                static fn (array $elements, string|Element|ElementList $value) => [
                    ...$elements,
                    ...match(true) {
                        $value instanceof Element => [$value],
                        $value instanceof ElementList => $value->elements(),
                        default => [], // Strings are considered simpleTypes - not elements
                    }
                ],
                [],
            )
        );
    }

    /**
     * @param non-empty-string $xml
     */
    public static function fromString(string $xml): self
    {
        $doc = Document::fromXmlString($xml);

        return new self(
            ...readChildren($doc->locateDocumentElement())->map(
                static fn (DOMElement $element): Element => Element::fromDOMElement($element)
            )
        );
    }

    public function append(Element $element): self
    {
        $this->elements[] = $element;

        return $this;
    }

    /**
     * @return list<Element>
     */
    public function elements(): array
    {
        return $this->elements;
    }

    public function hasElements(): bool
    {
        return (bool) $this->elements;
    }

    /**
     * @template R
     * @param Closure(Element): R $mapper
     * @return list<R>
     */
    public function traverse(Closure $mapper): array
    {
        return map($this->elements, $mapper);
    }

    public function value(): string
    {
        return implode('', $this->traverse(static fn (Element $element): string => $element->value()));
    }

    public function __toString()
    {
        return $this->value();
    }
}
