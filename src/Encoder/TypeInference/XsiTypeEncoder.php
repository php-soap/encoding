<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\TypeInference;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Engine\Metadata\Model\XsdType;
use VeeWee\Reflecta\Iso\Iso;

/**
 * @implements XmlEncoder<string, XsdType>
 */
final class XsiTypeEncoder implements XmlEncoder
{
    /**
     * @return Iso<string, XsdType>
     */
    public function iso(Context $context): Iso
    {
        return new Iso(
            // TODO : xsd:anyType should 'detect' e.g. xsd:string based on the provided value type.
            static fn(XsdType $raw): string => sprintf(
                '%s:%s',
                $context->namespaces->lookupNameFromNamespace($raw->getXmlNamespace())->unwrap(), // TODO : error-handling?
                $raw->getName() // TODO : or fallback? Don't we need a field with "actual xsd type" ?
            ),
            static fn(string $xml): XsdType => throw new \RuntimeException('Not implemented yet')
        );
    }
}
