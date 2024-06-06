<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\Xml\Reader\ElementValueReader;
use Soap\Encoding\Xml\Writer\ElementValueBuilder;
use Soap\Encoding\Xml\Writer\XsdTypeXmlElementWriter;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Xml\Dom\Document;

/**
 * @implements XmlEncoder<mixed, string>
 */
final class ElementEncoder implements XmlEncoder
{
    /**
     * @param XmlEncoder<mixed, string> $typeEncoder
     */
    public function __construct(
        private readonly XmlEncoder $typeEncoder
    ) {
    }

    /**
     * @return Iso<mixed, string>
     */
    public function iso(Context $context): Iso
    {
        $typeEncoder = $this->typeEncoder;

        return new Iso(
            /**
             * @psalm-param mixed $raw
             */
            static fn (mixed $raw): string => (new XsdTypeXmlElementWriter())(
                $context,
                (new ElementValueBuilder($context, $typeEncoder, $raw))
            ),
            /**
             * @psalm-param non-empty-string $xml
             * @psalm-return mixed
             */
            static fn (string $xml): mixed => (new ElementValueReader())(
                $context,
                $typeEncoder,
                Document::fromXmlString($xml)->locateDocumentElement(),
            )
        );
    }
}
