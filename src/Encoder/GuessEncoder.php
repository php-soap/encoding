<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use VeeWee\Reflecta\Iso\Iso;

/**
 * @implements XMLEncoder<string, mixed>
 */
class GuessEncoder implements XmlEncoder
{
    public function iso(Context $context): Iso
    {
        /*$type = $context->type;
        $meta = $type->getMeta();

        if ($type->getMeta()->isSimple()->unwrapOr(false)) {
            //$extends = $meta->extends()->map()->


        }*/

        return Iso::identity();
    }
}
