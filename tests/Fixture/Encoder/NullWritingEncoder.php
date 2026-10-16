<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Fixture\Encoder;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\Feature;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Xml\Node\Element;
use VeeWee\Reflecta\Iso\Iso;

/**
 * A third-party encoder that handles null itself and writes an element for it.
 *
 * @implements XmlEncoder<string|null, string, string, Element|string>
 */
final class NullWritingEncoder implements Feature\OptionalAware, XmlEncoder
{
    /**
     * @return Iso<string|null, string, string, Element|string>
     */
    public function iso(Context $context): Iso
    {
        return new Iso(
            static fn (?string $value): string => '<custom>' . ($value ?? 'none') . '</custom>',
            static fn (Element|string $xml): string => ($xml instanceof Element ? $xml : Element::fromString($xml))->element()->textContent,
        );
    }
}
