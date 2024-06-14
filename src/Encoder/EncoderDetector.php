<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Engine\Metadata\Model\XsdType;
use stdClass;
use WeakMap;

final class EncoderDetector
{
    /**
     * @var WeakMap<XsdType, XmlEncoder<mixed, string>>
     */
    private WeakMap $cache;

    public static function default(): self
    {
        /** @var self $self */
        static $self = new self();

        return $self;
    }

    private function __construct()
    {
        /** @var WeakMap<XsdType, XmlEncoder<mixed, string>> cache */
        $this->cache = new WeakMap();
    }

    /**
     * @return XmlEncoder<mixed, string>
     *
     * @psalm-suppress InvalidArgument, InvalidReturnType, PossiblyInvalidArgument, InvalidReturnStatement - The simple type detector could return string|null, but should not be an issue here.
     */
    public function __invoke(Context $context): XmlEncoder
    {
        $type = $context->type;
        if ($cached = $this->cache[$type] ?? null) {
            return $cached;
        }

        $meta = $type->getMeta();

        $encoder = match(true) {
            $meta->isSimple()->unwrapOr(false) => SimpleType\EncoderDetector::default()($context),
            default => $this->detectComplexTypeEncoder($type, $context),
        };

        if (!$encoder instanceof Feature\ListAware && $meta->isRepeatingElement()->unwrapOr(false)) {
            $encoder = new RepeatingElementEncoder($encoder);
        }

        $encoder = new ErrorHandlingEncoder($encoder);

        return $this->cache[$type] = $encoder;
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
