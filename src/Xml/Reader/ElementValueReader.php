<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Reader;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\TypeInference\XsiTypeDetector;
use VeeWee\Reflecta\Iso\Iso;
use function Psl\Type\string;
use function VeeWee\Xml\Dom\Locator\Node\value as readValue;

final class ElementValueReader
{
    public function __invoke(
        Context $context,
        Iso $typeIso,
        \DOMElement $element
    ): mixed {
        $iso = XsiTypeDetector::detectEncoderFromXmlElement($context, $element)
            ->map(
                static fn(XmlEncoder $encoder): Iso => $encoder->iso($context)
            )
            ->unwrapOr($typeIso);

        return $iso->from(
            readValue($element, string())
        );
    }
}
