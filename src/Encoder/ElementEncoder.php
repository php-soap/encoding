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
        $context = $this->typeEncoder instanceof Feature\ElementContextEnhancer
            ? $this->typeEncoder->enhanceElementContext($context)
            : $context;

        return new Iso(
            /**
             * @psalm-param mixed $raw
             */
            static fn (mixed $raw): string => (new XsdTypeXmlElementWriter())(
                $context,
                (new ElementValueBuilder($typeEncoder instanceof Feature\ElementContextEnhancer ? ($context = $typeEncoder->resolveXsiType($context, $raw)) : $context, $typeEncoder, $raw))
            ),
            /**
             * @psalm-param non-empty-string|Element $xml
             * @psalm-return mixed
             */
            static fn (Element|string $xml): mixed => (new ElementValueReader())(
                $context,
                $typeEncoder,
                ($xml instanceof Element ? $xml : Element::fromString($xml))->element()
            )
        );
    }
}
