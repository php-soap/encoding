<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Reader;

use DOMElement;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use VeeWee\Reflecta\Iso\Iso;
use function Psl\Type\string;
use function VeeWee\Xml\Dom\Locator\Node\value as readValue;

final class ElementValueReader
{
    /**
     * @param XmlEncoder<mixed, string> $encoder
     * @psalm-return mixed
     */
    public function __invoke(
        Context $context,
        XmlEncoder $encoder,
        DOMElement $element
    ): mixed {
        return self::forEncoder($context, $encoder, $element);
    }

    /**
     * @param XmlEncoder<mixed, string> $encoder
     * @psalm-return mixed
     */
    public static function forEncoder(Context $context, XmlEncoder $encoder, DOMElement $element): mixed
    {
        return $encoder->iso($context)->from(
            readValue($element, string())
        );
    }

    /**
     * @param Iso<mixed, string> $iso
     * @psalm-return mixed
     */
    public static function forIso(Iso $iso, DOMElement $element): mixed
    {
        return $iso->from(
            readValue($element, string())
        );
    }
}
