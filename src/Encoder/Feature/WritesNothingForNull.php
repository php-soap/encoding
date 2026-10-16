<?php declare(strict_types=1);

namespace Soap\Encoding\Encoder\Feature;

use Soap\Encoding\Encoder\Context;

/**
 * Tells whether encoding null in this context produces an empty string, so an object encoder can leave the element out without calling the encoder.
 */
interface WritesNothingForNull
{
    public function writesNothingForNull(Context $context): bool;
}
