<?php declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Closure;
use Soap\Encoding\TypeInference\XsiTypeDetector;
use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Node\ElementList;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Reflecta\Iso\IsoInterface;
use function Psl\Type\non_empty_string;

/**
 * @implements XmlEncoder<mixed, mixed, string, Element|string>
 * @implements Feature\DecoratingEncoder<mixed, mixed, string|null, Element|ElementList|string|null>
 */
final readonly class XsiTypeEncoder implements Feature\DecoratingEncoder, Feature\ElementAware, XmlEncoder
{
    /**
     * @param XmlEncoder<mixed, mixed, string|null, Element|ElementList|string|null> $encoder
     */
    public function __construct(
        private XmlEncoder $encoder
    ) {
    }

    /**
     * @return XmlEncoder<mixed, mixed, string|null, Element|ElementList|string|null>
     */
    public function decoratedEncoder(): XmlEncoder
    {
        return $this->encoder;
    }

    /**
     * @return Iso<mixed, mixed, string, Element|string>
     */
    public function iso(Context $context): Iso
    {
        $innerIso = $this->encoder->iso($context);
        $detectEncoder = $this->encoder instanceof Feature\DisregardXsiInformation
            ? null
            : XsiTypeDetector::encoderDetectorForContext($context);

        return new Iso(
            function (mixed $value) use ($innerIso) : string {
                return $this->to($innerIso, $value);
            },
            function (string|Element $value) use ($context, $innerIso, $detectEncoder) : mixed {
                return $this->from(
                    $context,
                    $innerIso,
                    $detectEncoder,
                    ($value instanceof Element ? $value : Element::fromString(non_empty_string()->assert($value)))
                );
            }
        );
    }

    /**
     * @param IsoInterface<mixed, mixed, string|null, Element|ElementList|string|null> $innerIso
     */
    private function to(IsoInterface $innerIso, mixed $value): string
    {
        // There is no way to know what xsi:type to use when encoding any type.
        // The type defined in the wsdl will always be used to encode the value.
        // If you want more control over the encoded type, please control how to encode by using the MatchingValueEncoder.
        //
        // The inner XML output slot is `string|null` because it mirrors the shared aggregate encoder type,
        // but element encoders always produce a (non-empty) string in the to() direction: the cast only collapses
        // the type-level nullability that originates from attribute encoders elsewhere in the aggregate.
        return (string) $innerIso->to($value);
    }

    /**
     * @param IsoInterface<mixed, mixed, string|null, Element|ElementList|string|null> $innerIso
     * @param (Closure(\Dom\Element): (FixedIsoEncoder<mixed, mixed, string|null, Element|ElementList|string|null>|null))|null $detectEncoder
     */
    private function from(Context $context, IsoInterface $innerIso, ?Closure $detectEncoder, Element $value): mixed
    {
        $iso = ($detectEncoder ? $detectEncoder($value->element()) : null)?->iso($context) ?? $innerIso;

        /** @psalm-suppress ImplicitToStringCast - Encoders accept string|Element in from() */
        return $iso->from($value);
    }
}
