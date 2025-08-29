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
        return new Iso(
            function (mixed $value) use ($context) : string {
                return $this->to($context, $value);
            },
            function (string|Element $value) use ($context) : mixed {
                return $this->from(
                    $context,
                    ($value instanceof Element ? $value : Element::fromString(non_empty_string()->assert($value)))
                );
            }
        );
    }

    private function to(Context $context, mixed $value): string
    {
        // There is no way to know what xsi:type to use when encoding any type.
        // The type defined in the wsdl will always be used to encode the value.
        // If you want more control over the encoded type, please control how to encode by using the MatchingValueEncoder.
        return $this->encoder->iso($context)->to($value);
    }

    private function from(Context $context, Element $value): mixed
    {
        /** @var XmlEncoder<string, mixed> $encoder */
        $encoder = match (true) {
            $this->encoder instanceof Feature\DisregardXsiInformation => $this->encoder,
            default => XsiTypeDetector::detectEncoderFromXmlElement($context, $value->element())->unwrapOr($this->encoder)
        };

        return $encoder->iso($context)->from($value);
    }
}
