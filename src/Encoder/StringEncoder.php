<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Xml\Dom\Document;
use function Psl\Type\string;
use function VeeWee\Xml\Dom\Builder\element;
use function VeeWee\Xml\Dom\Builder\namespaced_element;
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
    public function iso(Context $context): Iso
    {
        $type = $context->type;

        return new Iso(
            static function(string $raw) use ($type): string {
                $doc = Document::empty();
                $value = buildValue($raw);
                $name = $type->getXmlNamespaceName() ? $type->getXmlNamespaceName().':'.$type->getName() : $type->getName();

                $doc->manipulate(append( // TODO --> Shortcut for building xml.
                    ...$doc->build(
                        $type->getXmlNamespace()
                            ? namespaced_element($type->getXmlNamespace(), $name, $value)
                            : element($type->getName(), $value)
                    )
                ));

                return xml_string()($doc->toUnsafeDocument()->documentElement); // TODO : shortcut for element XMl
            },
            static function(string $xml): string {
                return readValue(Document::fromXmlString($xml)->map(document_element()), string());
            }
        );
    }
}
