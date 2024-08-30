<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\Feature;

/**
 * Tells the decoder to disregard any xsi:type information on the element when decoding an element.
 * It will use the original provided decoder by default and won't try to guess the decoder based on xsi:type.
 */
interface DisregardXsiInformation
{
}
