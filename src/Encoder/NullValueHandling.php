<?php declare(strict_types=1);

namespace Soap\Encoding\Encoder;

/**
 * What ObjectEncoder does with a property that is null or absent from the PHP value.
 * An absent property reads as null, unless the case below tells them apart.
 */
enum NullValueHandling
{
    /**
     * Pass null to the property's encoder and write what it returns.
     * For a nillable element that is the element with xsi:nil="true".
     */
    case Write;

    /**
     * Write nothing for the property, so the element is left out.
     */
    case Skip;

    /**
     * Write nothing when the property is absent from the PHP value.
     * When it is set to null, behave like Write.
     */
    case SkipWhenAbsent;
}
