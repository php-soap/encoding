<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Fixture\Encoder;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\Feature;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Node\ElementLookup;
use Soap\Engine\Metadata\Model\Property;
use Soap\Engine\Metadata\Model\Type;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Reflecta\Lens\Lens;

/**
 * The ThrowingLensEncoder without DisregardXsiInformation, so the encoder detector wraps it in an XsiTypeEncoder.
 * Lens reads are counted on ThrowingLensEncoder.
 *
 * @implements XmlEncoder<string|null, string, string, Element|string>
 * @template-implements Feature\ProvidesObjectEncoderLens<object, object, mixed, mixed>
 * @template-implements Feature\ProvidesObjectDecoderLens<ElementLookup, ElementLookup, mixed, mixed>
 */
final class XsiDetectedLensEncoder implements
    Feature\ProvidesObjectDecoderLens,
    Feature\ProvidesObjectEncoderLens,
    XmlEncoder
{
    /**
     * @return Lens<object, object, mixed, mixed>
     */
    public static function createObjectEncoderLens(Type $parentType, Property $currentProperty): Lens
    {
        return ThrowingLensEncoder::createObjectEncoderLens($parentType, $currentProperty);
    }

    /**
     * @return Lens<ElementLookup, ElementLookup, mixed, mixed>
     */
    public static function createObjectDecoderLens(Type $parentType, Property $currentProperty): Lens
    {
        return ThrowingLensEncoder::createObjectDecoderLens($parentType, $currentProperty);
    }

    /**
     * @return Iso<string|null, string, string, Element|string>
     */
    public function iso(Context $context): Iso
    {
        return (new ThrowingLensEncoder())->iso($context);
    }
}
