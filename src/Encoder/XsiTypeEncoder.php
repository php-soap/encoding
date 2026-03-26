<?php declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\TypeInference\XsiTypeDetector;
use Soap\Encoding\Xml\Node\Element;
use VeeWee\Reflecta\Iso\Iso;
use function Psl\Type\non_empty_string;

/**
 * @implements XmlEncoder<mixed, string>
 */
final readonly class XsiTypeEncoder implements Feature\ElementAware, XmlEncoder
{
    /**
     * @param XmlEncoder<mixed, string> $encoder
     */
    public function __construct(
        private XmlEncoder $encoder
    ) {
    }

    /**
     * @return Iso<mixed, string>
     */
    public function iso(Context $context): Iso
    {
        $innerIso = $this->encoder->iso($context);

        return new Iso(
            function (mixed $value) use ($innerIso) : string {
                return $this->to($innerIso, $value);
            },
            function (string|Element $value) use ($context, $innerIso) : mixed {
                return $this->from(
                    $context,
                    $innerIso,
                    ($value instanceof Element ? $value : Element::fromString(non_empty_string()->assert($value)))
                );
            }
        );
    }

    /**
     * @param Iso<mixed, string> $innerIso
     */
    private function to(Iso $innerIso, mixed $value): string
    {
        // There is no way to know what xsi:type to use when encoding any type.
        // The type defined in the wsdl will always be used to encode the value.
        // If you want more control over the encoded type, please control how to encode by using the MatchingValueEncoder.
        return $innerIso->to($value);
    }

    /**
     * @param Iso<mixed, string> $innerIso
     */
    private function from(Context $context, Iso $innerIso, Element $value): mixed
    {
        $iso = match (true) {
            $this->encoder instanceof Feature\DisregardXsiInformation => $innerIso,
            default => XsiTypeDetector::detectEncoderFromXmlElement($context, $value->element())
                ->map(static fn (XmlEncoder $encoder): Iso => $encoder->iso($context))
                ->unwrapOr($innerIso),
        };

        return $iso->from($value);
    }
}
