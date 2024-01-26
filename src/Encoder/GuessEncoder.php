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
        $type = $context->type;
        $meta = $type->getMeta();

        $encoder =  match (true) {
            $meta->isList()->unwrapOr(false) => new ListEncoder(),
            $meta->isSimple()->unwrapOr(false) => new ExtendedSimpleTypeEncoder(),
            default => new ObjectEncoder(\stdClass::class)
        };

        return $encoder->iso($context);
    }
}
