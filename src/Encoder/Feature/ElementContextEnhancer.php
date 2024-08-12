<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\Feature;

use Soap\Encoding\Encoder\Context;

/**
 * By implementing this feature on your encoder, you can change the context of the wrapping element.
 * This can be used on simpleType encoders to dictate how the wrapped element should be built.
 *
 * Example usages:
 * - Opt-in on xsi:type information for literal documents
 * - ... ? :)
 */
interface ElementContextEnhancer
{
    public function enhanceElementContext(Context $context): Context;
    public function resolveXsiType(Context $context, mixed $value): Context;
}
