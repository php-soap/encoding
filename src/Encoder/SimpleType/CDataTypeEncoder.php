<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\Feature;
use Soap\Encoding\Encoder\XmlEncoder;
use VeeWee\Reflecta\Iso\Iso;

/**
 * This encoder works exactly the same as the string encoder.
 * However, it implements the CData feature.
 * When writing the data to the XML element, it will be wrapped in a CDATA section.
 *
 * @psalm-suppress UnusedClass
 * @implements XmlEncoder<string, string>
 */
final class CDataTypeEncoder implements Feature\CData, XmlEncoder
{
    /**
     * @return Iso<string, string>
     */
    public function iso(Context $context): Iso
    {
        return (new StringTypeEncoder())->iso($context);
    }
}
