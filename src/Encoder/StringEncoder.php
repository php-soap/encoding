<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Xml\Dom\Document;
use function Psl\Type\string;
use function VeeWee\Xml\Dom\Builder\element;
use function VeeWee\Xml\Dom\Locator\document_element;
use function VeeWee\Xml\Dom\Manipulator\append;
use function VeeWee\Xml\Dom\Mapper\xml_string;
use function VeeWee\Xml\Dom\Builder\value as buildValue;
use function VeeWee\Xml\Dom\Locator\Node\value as readValue;

/**
 * @implements XmlEncoder<String, String>
 */
class StringEncoder implements XmlEncoder
{
    /**
     * @return Iso<string, string>
     */
    public function iso(): Iso
    {
        return new Iso(
            static function(string $raw): string {
                $doc = Document::empty();
                $doc->manipulate(append(
                    ...$doc->build(
                        element('root', buildValue($raw))
                    )
                ));

                return xml_string()($doc->toUnsafeDocument()->documentElement);
            },
            static function(string $xml): string {
                return readValue(Document::fromXmlString($xml)->map(document_element()), string());
            }
        );
    }
}
