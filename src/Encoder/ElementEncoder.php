<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\Encoder\SimpleType\GuessTypeEncoder;
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
     * @param XmlEncoder<string, T>|null $typeEncoder
     */
    public function __construct(
        private ?XmlEncoder $typeEncoder = null
    ) {
        $this->typeEncoder ??= new GuessTypeEncoder();
    }

    /**
     * @return Iso<string, T>
     */
    public function iso(Context $context): Iso
    {
        $type = $context->type;

        return $this->typeEncoder->iso($context)->compose(
            new Iso(
                static function(string $raw) use ($type): string {
                    $value = buildValue($raw);

                    return (new XsdTypeXmlElementWriter($type))($value);
                },
                static function(string $xml): string {
                    return readValue(
                        Document::fromXmlString($xml)->locateDocumentElement(),
                        string()
                    );
                }
            )
        );
    }
}
