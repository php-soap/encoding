<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\Xml\XsdTypeXmlElementWriter;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Xml\Dom\Document;
use function Psl\Type\string;
use function VeeWee\Xml\Dom\Locator\Node\value as readValue;
use function VeeWee\Xml\Writer\Builder\value as buildValue;

/**
 * @template T of mixed
 * @implements XmlEncoder<string, T>
 */
final class ElementEncoder implements XmlEncoder
{
    /**
     * @param XmlEncoder<string, T> $typeEncoder
     */
    public function __construct(
        private readonly XmlEncoder $typeEncoder
    ) {
    }

    /**
     * @return Iso<string, T>
     */
    public function iso(Context $context): Iso
    {
        $typeIso = $this->typeEncoder->iso($context);

        return new Iso(
            static fn(mixed $raw): string => (new XsdTypeXmlElementWriter())(
                $context,
                buildValue($typeIso->to($raw))
            ),
            static fn(string $xml): mixed => $typeIso->from(
                readValue(
                    Document::fromXmlString($xml)->locateDocumentElement(),
                    string()
                )
            )
        );
    }
}
