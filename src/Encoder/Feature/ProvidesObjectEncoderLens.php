<?php declare(strict_types=1);

namespace Soap\Encoding\Encoder\Feature;

use Soap\Engine\Metadata\Model\Property;
use Soap\Engine\Metadata\Model\Type;
use VeeWee\Reflecta\Lens\Lens;

/**
 * When an encoder implements this feature interface, it knows how to create a lens that will be applied on the parent data that is being encoded.
 *
 * @template-covariant S
 * @template-covariant A
 */
interface ProvidesObjectEncoderLens
{
    /**
     * @return Lens<S, A>
     */
    public static function createObjectEncoderLens(Type $parentType, Property $currentProperty): Lens;
}
