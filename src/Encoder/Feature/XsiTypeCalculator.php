<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\Feature;

use Soap\Encoding\Encoder\Context;

/**
 * By implementing this feature on your simpleType encoder, you can let the encoder decide what xsi:type attribute should be set to for a given value.
 */
interface XsiTypeCalculator
{
    /**
     * @return string The value for the xsi:type attribute
     *
     * A sensible default fallback function is provided in the `ElementValueBuilder` class.
     */
    public function resolveXsiTypeForValue(Context $context, mixed $value): string;


    /**
     * Tells the XsiAttributeBuilder that the prefix of the xsi:type should be imported as a xmlns namespace.
     *
     * A sensible default fallback function is provided in the `ElementValueBuilder` class.
     */
    public function shouldIncludeXsiTargetNamespace(Context $context): bool;
}
