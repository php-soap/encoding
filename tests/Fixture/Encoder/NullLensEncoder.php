<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Fixture\Encoder;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\Feature;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Xml\Node\Element;
use Soap\Engine\Metadata\Model\Property;
use Soap\Engine\Metadata\Model\Type;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Reflecta\Lens\Lens;

/**
 * A third-party encoder whose object encoder lens reads null, whatever the object holds.
 *
 * @implements XmlEncoder<string|null, string, string, Element|string>
 * @template-implements Feature\ProvidesObjectEncoderLens<object, object, mixed, mixed>
 */
final class NullLensEncoder implements
    Feature\DisregardXsiInformation,
    Feature\ProvidesObjectEncoderLens,
    XmlEncoder
{
    /**
     * @return Lens<object, object, mixed, mixed>
     */
    public static function createObjectEncoderLens(Type $parentType, Property $currentProperty): Lens
    {
        /** @var Lens<object, object, mixed, mixed> */
        return Lens::readonly(static fn (object $data): mixed => null);
    }

    /**
     * @return Iso<string|null, string, string, Element|string>
     */
    public function iso(Context $context): Iso
    {
        return (new ThrowingLensEncoder())->iso($context);
    }
}
