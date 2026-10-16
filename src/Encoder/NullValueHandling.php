<?php declare(strict_types=1);

namespace Soap\Encoding\Encoder;

/**
 * What an object encoder does with a property whose value is null.
 */
enum NullValueHandling
{
    /**
     * Let the encoder write it.
     */
    case Write;

    /**
     * The encoder writes nothing for null, so the element is left out.
     */
    case Skip;

    /**
     * Leave the element out when the property is absent from the PHP value, let the encoder write xsi:nil when it is set to null.
     */
    case SkipWhenAbsent;
}
