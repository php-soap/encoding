<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Engine\Metadata\Model\TypeMeta;
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

        if ($meta->isList()->unwrapOr(false)) {
            return (new ListEncoder())->iso($context);
        }

        if ($meta->isSimple()->unwrapOr(false)) {
            return $meta->extends()
                ->map(static fn ($extends) : XmlEncoder => $context->registry->findByNamespaceName(
                    $extends['namespace'],
                    $extends['type'],
                ))
                ->unwrapOr(new ElementEncoder())
                ->iso($context);
        }

        return (new ObjectEncoder(\stdClass::class))->iso($context);
    }
}
