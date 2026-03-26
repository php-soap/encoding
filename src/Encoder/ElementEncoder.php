<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Reader\ElementValueReader;
use Soap\Encoding\Xml\Writer\ElementValueBuilder;
use Soap\Encoding\Xml\Writer\XsdTypeXmlElementWriter;
use VeeWee\Reflecta\Iso\Iso;

/**
 * @implements XmlEncoder<mixed, string>
 */
final class ElementEncoder implements Feature\ElementAware, XmlEncoder
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
        $context = $this->typeEncoder instanceof Feature\ElementContextEnhancer
            ? $this->typeEncoder->enhanceElementContext($context)
            : $context;

        $typeIso = $typeEncoder->iso($context);

        return new Iso(
            /**
             * @psalm-param mixed $raw
             */
            static fn (mixed $raw): string => (new XsdTypeXmlElementWriter())(
                $context,
                ElementValueBuilder::fromIso($context, $typeEncoder, $typeIso, $raw)
            ),
            /**
             * @psalm-param non-empty-string|Element $xml
             * @psalm-return mixed
             */
            static fn (Element|string $xml): mixed => ElementValueReader::forIso(
                $typeIso,
                ($xml instanceof Element ? $xml : Element::fromString($xml))->element()
            )
        );
    }
}
