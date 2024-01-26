<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\Xml\XsdTypeXmlElementBuilder;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Xml\Dom\Document;
use function Psl\Type\string;
use function VeeWee\Xml\Dom\Locator\Node\value as readValue;
use function VeeWee\Xml\Writer\Builder\value as buildValue;

/**
 * @implements XmlEncoder<string, string>
 */
class StringEncoder implements XmlEncoder
{
    /**
     * @return Iso<string, string>
     */
    public function iso(Context $context): Iso
    {
        $type = $context->type;

        return new Iso(
            static function(string $raw) use ($type): string {
                $value = buildValue($raw);

                return (new XsdTypeXmlElementBuilder($type))($value);
            },
            static function(string $xml): string {
                return readValue(
                    Document::fromXmlString($xml)->locateDocumentElement(),
                    string()
                );
            }
        );
    }
}
