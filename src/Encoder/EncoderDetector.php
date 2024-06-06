<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Engine\Metadata\Model\XsdType;
use stdClass;

final class EncoderDetector
{
    /**
     * @return XmlEncoder<mixed, string>
     *
     * @psalm-suppress InvalidReturnType, PossiblyInvalidArgument, InvalidReturnStatement - The simple type detector could return string|null, but should not be an issue here.
     */
    public function __invoke(Context $context): XmlEncoder
    {
        $type = $context->type;
        $meta = $type->getMeta();

        $encoder = match(true) {
            $meta->isSimple()->unwrapOr(false) => (new SimpleType\EncoderDetector())($context),
            default => $this->detectComplexTypeEncoder($type, $context),
        };

        if (!$encoder instanceof Feature\ListAware && $meta->isRepeatingElement()->unwrapOr(false)) {
            $encoder = new RepeatingElementEncoder($encoder);
        }

        return new ErrorHandlingEncoder($encoder);
    }

    /**
     * @return XmlEncoder<mixed, string>
     */
    private function detectComplexTypeEncoder(XsdType $type, Context $context): XmlEncoder
    {
        $meta = $type->getMeta();

        // Try to find a direct match:
        if ($context->registry->hasRegisteredComplexTypeForXsdType($type)) {
            return $context->registry->findComplexEncoderByXsdType($type);
        }

        // Try to find a match for the extended complex type:
        // Or fallback to the default object encoder.
        return $meta->extends()
            ->filter(static fn ($extend): bool => !($extend['isSimple'] ?? false))
            ->map(static fn ($extends) : XmlEncoder => $context->registry->findComplexEncoderByNamespaceName(
                $extends['namespace'],
                $extends['type'],
            ))
            ->unwrapOr(new ObjectEncoder(stdClass::class));
    }
}
