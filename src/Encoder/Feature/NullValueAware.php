<?php declare(strict_types=1);

namespace Soap\Encoding\Encoder\Feature;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\NullValueHandling;

/**
 * Tells an object encoder what to do with a null value in this context, so it can leave the element out without calling the encoder.
 */
interface NullValueAware
{
    public function nullValueHandling(Context $context): NullValueHandling;
}
