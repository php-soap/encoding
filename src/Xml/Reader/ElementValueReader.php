<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Reader;

use Dom\Element;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use VeeWee\Reflecta\Iso\IsoInterface;

final class ElementValueReader
{
    /**
     * @param XmlEncoder<mixed, mixed, string, string> $encoder
     * @psalm-return mixed
     */
    public function __invoke(
        Context $context,
        XmlEncoder $encoder,
        Element $element
    ): mixed {
        return self::forEncoder($context, $encoder, $element);
    }

    /**
     * @param XmlEncoder<mixed, mixed, string, string> $encoder
     * @psalm-return mixed
     */
    public static function forEncoder(Context $context, XmlEncoder $encoder, Element $element): mixed
    {
        return $encoder->iso($context)->from($element->substitutedNodeValue);
    }

    /**
     * @param IsoInterface<mixed, mixed, string, string> $iso
     * @psalm-return mixed
     */
    public static function forIso(IsoInterface $iso, Element $element): mixed
    {
        return $iso->from($element->substitutedNodeValue);
    }
}
