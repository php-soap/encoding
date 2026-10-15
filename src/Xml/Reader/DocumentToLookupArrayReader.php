<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Reader;

use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Node\ElementList;
use Soap\Encoding\Xml\Node\ElementLookup;

/**
 * @psalm-type LookupArrayValue = string|Element|ElementList
 * @psalm-type LookupArray = array<string, LookupArrayValue>
 */
final class DocumentToLookupArrayReader
{
    /**
     * @return LookupArray
     *
     * @see ElementLookup::toArray()
     */
    public function __invoke(Element $xml): array
    {
        return ElementLookup::fromElement($xml)->toArray();
    }
}
