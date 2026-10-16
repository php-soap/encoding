<?php declare(strict_types=1);

namespace Soap\Encoding\Encoder\Feature;

use Soap\Encoding\Xml\Node\ElementLookup;
use Soap\Engine\Metadata\Model\Property;
use Soap\Engine\Metadata\Model\Type;
use VeeWee\Reflecta\Lens\LensInterface;

/**
 * When an encoder implements this feature interface, it knows how to create a lens that will be applied on the parent data that is being decoded.
 * The lens reads from the ElementLookup of the parent element, for example $lookup->element('name') or $lookup->attribute('name').
 *
 * @template-covariant S of ElementLookup
 * @template-covariant T of ElementLookup
 * @template-covariant A
 * @template-covariant B
 */
interface ProvidesObjectDecoderLens
{
    /**
     * @return LensInterface<S, T, A, B>
     */
    public static function createObjectDecoderLens(Type $parentType, Property $currentProperty): LensInterface;
}
