<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\Cache\ScopedCache;
use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Node\ElementList;
use Soap\Engine\Metadata\Model\XsdType;
use stdClass;

final class EncoderDetector
{
    public static function default(): self
    {
        /** @var self $self */
        static $self = new self();

        return $self;
    }

    /**
     * @return ScopedCache<XsdType, XmlEncoder<mixed, mixed, string|null, Element|ElementList|string|null>>
     *
     * @psalm-suppress LessSpecificReturnStatement, MoreSpecificReturnType, MixedReturnStatement
     */
    private static function cache(): ScopedCache
    {
        static $cache = new ScopedCache();

        return $cache;
    }

    /**
     * @return XmlEncoder<mixed, mixed, string|null, Element|ElementList|string|null>
     */
    public function __invoke(Context $context): XmlEncoder
    {
        return self::cache()->lookup(
            $context->type,
            'encoder',
            fn (): XmlEncoder => $this->detect($context)
        );
    }

    /**
     * @return XmlEncoder<mixed, mixed, string|null, Element|ElementList|string|null>
     */
    private function detect(Context $context): XmlEncoder
    {
        $meta = $context->type->getMeta();

        return $this->enhanceEncoder(
            $context,
            match(true) {
                $meta->isSimple()->unwrapOr(false) => SimpleType\EncoderDetector::default()($context),
                default => $this->detectComplexTypeEncoder($context->type, $context)
            }
        );
    }

    /**
     * @param XmlEncoder<mixed, mixed, string|null, Element|ElementList|string|null> $encoder
     * @return XmlEncoder<mixed, mixed, string|null, Element|ElementList|string|null>
     */
    private function enhanceEncoder(Context $context, XmlEncoder $encoder): XmlEncoder
    {
        $meta = $context->type->getMeta();
        $isSimple = $meta->isSimple()->unwrapOr(false);

        if (!$isSimple && !$encoder instanceof Feature\DisregardXsiInformation && !$context->skipXsiTypeDetection) {
            $encoder = new XsiTypeEncoder($encoder);
        }

        if (!$encoder instanceof Feature\ListAware && $meta->isRepeatingElement()->unwrapOr(false)) {
            $encoder = new RepeatingElementEncoder($encoder);
        }

        if (!$encoder instanceof Feature\OptionalAware && $meta->isNullable()->unwrapOr(false)) {
            $encoder = new OptionalElementEncoder($encoder);
        }

        return new ErrorHandlingEncoder($encoder);
    }

    /**
     * @return XmlEncoder<mixed, mixed, string, Element|ElementList|string>
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
