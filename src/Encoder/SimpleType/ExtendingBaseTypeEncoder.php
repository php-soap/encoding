<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\ElementEncoder;
use Soap\Encoding\Encoder\XmlEncoder;
use VeeWee\Reflecta\Iso\Iso;

/**
 * @implements XmlEncoder<string, string>
 */
final class ExtendingBaseTypeEncoder implements XmlEncoder
{
    public function iso(Context $context): Iso
    {
        $meta = $context->type->getMeta();
        return $meta->extends()
            ->filter(static fn($extend): bool => $extend['isSimple'])
            ->map(static fn ($extends) : XmlEncoder => $context->registry->findByNamespaceName(
                $extends['namespace'],
                $extends['type'],
            ))
            ->unwrapOr(new ElementEncoder())
            ->iso($context);
    }
}
