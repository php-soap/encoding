<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\Encoder\SimpleType\ScalarTypeEncoder;
use Soap\Engine\Metadata\Model\XsdType;

final class EncoderDetector
{
    /**
     * @return XmlEncoder<string, mixed>
     */
    public function __invoke(Context $context): XmlEncoder
    {
        $type = $context->type;
        $meta = $type->getMeta();

        // TODO : List / Nullable should be wrapped in a different location.
        // TODO : otherwise, it only works when guessed and not when fetched by type directly from the registry.
        if ($meta->isList()->unwrapOr(false)) {
            return new ListEncoder(
                $this->__invoke(
                    $context->withType(
                        $type->withMeta(
                            static fn($meta): mixed => $meta->withIsList(false)
                        )
                    )
                )
            );
        }

        if ($meta->isSimple()->unwrapOr(false)) {
            $encoder = $this->detectSimpleTypeEncoder($type, $context);
            if ($meta->isElement()->unwrapOr(false)) {
                $encoder = new ElementEncoder($encoder);
            }

            return $encoder;
        }

        return $context->registry->findComplexEncoderByXsdType($type);
    }

    /**
     * @return XmlEncoder<string, mixed>
     */
    private function detectSimpleTypeEncoder(XsdType $type, Context $context): XmlEncoder
    {
        $meta = $type->getMeta();

        // Try to find a direct match:
        if ($context->registry->hasRegisteredSimpleTypeForXsdType($type)) {
            return $context->registry->findSimpleEncoderByXsdType($type);
        }

        // Try to find a match for the extended simple type:
        // Or fallback to the default scalar encoder.
        return $meta->extends()
            ->filter(static fn($extend): bool => $extend['isSimple'])
            ->map(static fn ($extends) : XmlEncoder => $context->registry->findSimpleEncoderByNamespaceName(
                $extends['namespace'],
                $extends['type'],
            ))
            ->unwrapOr(new ScalarTypeEncoder());
    }
}
