<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\Xml\Reader\ElementValueReader;
use Soap\Encoding\Xml\Writer\ElementValueBuilder;
use Soap\Encoding\Xml\Writer\XsdTypeXmlElementWriter;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Xml\Dom\Document;

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
        $typeEncoder = $this->typeEncoder;
        $typeIso = $typeEncoder->iso($context);

        return new Iso(
            static fn(mixed $raw): string => (new XsdTypeXmlElementWriter())(
                $context,
                (new ElementValueBuilder($context, $typeIso, $raw))
            ),
            static fn(string $xml): mixed => (new ElementValueReader())(
                $context,
                $typeEncoder,
                Document::fromXmlString($xml)->locateDocumentElement(),
            )
        );
    }
}
