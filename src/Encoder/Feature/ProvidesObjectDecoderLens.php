<?php declare(strict_types=1);

namespace Soap\Encoding\Encoder\Feature;

use Soap\Engine\Metadata\Model\Property;
use Soap\Engine\Metadata\Model\Type;
use VeeWee\Reflecta\Lens\Lens;

/**
 * When an encoder implements this feature interface, it knows how to create a lens that will be applied on the parent data that is being decoded.
 *
 * @template-covariant S
 * @template-covariant A
 */
interface ProvidesObjectDecoderLens
{
    /**
     * @return Lens<S, A>
     */
    public static function createObjectDecoderLens(Type $parentType, Property $currentProperty): Lens;
}
