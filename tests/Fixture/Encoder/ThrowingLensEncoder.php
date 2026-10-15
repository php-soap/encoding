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
use function VeeWee\Reflecta\Lens\index;
use function VeeWee\Reflecta\Lens\property;

/**
 * A third-party encoder whose object lenses throw when the property is missing, like reflecta's index() and property() do.
 *
 * @implements XmlEncoder<string|null, string, string, Element|string>
 * @template-implements Feature\ProvidesObjectEncoderLens<object, object, mixed, mixed>
 * @template-implements Feature\ProvidesObjectDecoderLens<array, array, mixed, mixed>
 */
final class ThrowingLensEncoder implements
    Feature\DisregardXsiInformation,
    Feature\ProvidesObjectDecoderLens,
    Feature\ProvidesObjectEncoderLens,
    XmlEncoder
{
    public static int $encoderLensReads = 0;
    public static int $decoderLensReads = 0;

    /**
     * @return Lens<object, object, mixed, mixed>
     */
    public static function createObjectEncoderLens(Type $parentType, Property $currentProperty): Lens
    {
        $lens = property($currentProperty->getName());

        /** @var Lens<object, object, mixed, mixed> */
        return Lens::readonly(static function (object $data) use ($lens): mixed {
            self::$encoderLensReads++;

            return $lens->get($data);
        });
    }

    /**
     * @return Lens<array, array, mixed, mixed>
     */
    public static function createObjectDecoderLens(Type $parentType, Property $currentProperty): Lens
    {
        $lens = index($currentProperty->getName());

        /** @var Lens<array, array, mixed, mixed> */
        return Lens::readonly(static function (array $data) use ($lens): mixed {
            self::$decoderLensReads++;

            return $lens->get($data);
        });
    }

    /**
     * @return Iso<string|null, string, string, Element|string>
     */
    public function iso(Context $context): Iso
    {
        return new Iso(
            static fn (?string $value): string => $value === null ? '' : '<custom>' . $value . '</custom>',
            static fn (Element|string $xml): string => ($xml instanceof Element ? $xml : Element::fromString($xml))->element()->textContent,
        );
    }
}
