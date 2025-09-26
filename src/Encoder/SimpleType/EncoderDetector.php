<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\ElementEncoder;
use Soap\Encoding\Encoder\Feature;
use Soap\Encoding\Encoder\OptionalElementEncoder;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Encoder\XsiTypeEncoder;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use function Psl\Iter\any;

final class EncoderDetector
{
    public static function default(): self
    {
        /** @var self $self */
        static $self = new self();

        return $self;
    }

    /**
     * @return XmlEncoder<mixed, string|null>
     */
    public function __invoke(Context $context): XmlEncoder
    {
        return $this->enhanceEncoder(
            $context,
            $this->detectSimpleTypeEncoder($context)
        );
    }

    /**
     * @param XmlEncoder<mixed, string> $encoder
     * @return XmlEncoder<mixed, string|null>
     */
    private function enhanceEncoder(Context $context, XmlEncoder $encoder): XmlEncoder
    {
        $type = $context->type;
        $meta = $type->getMeta();

        if (!$encoder instanceof Feature\ListAware && $this->detectIsListType($type)) {
            $encoder = new SimpleListEncoder($encoder);
        }

        if ($meta->isAttribute()->unwrapOr(false)) {
            return new AttributeValueEncoder($encoder);
        }

        if ($meta->isElement()->unwrapOr(false)) {
            if (!$encoder instanceof Feature\ElementAware) {
                $encoder = new ElementEncoder($encoder);
            }

            if (!$encoder instanceof Feature\DisregardXsiInformation && $context->bindingUse === BindingUse::ENCODED) {
                $encoder = new XsiTypeEncoder($encoder);
            }

            if ($meta->isNullable()->unwrapOr(false) && !$encoder instanceof Feature\OptionalAware) {
                $encoder = new OptionalElementEncoder($encoder);
            }
        }

        return $encoder;
    }

    /**
     * @return XmlEncoder<mixed, string>
     */
    private function detectSimpleTypeEncoder(Context $context): XmlEncoder
    {
        $type = $context->type;
        $meta = $type->getMeta();

        // Try to find a direct match:
        if ($context->registry->hasRegisteredSimpleTypeForXsdType($type)) {
            return $context->registry->findSimpleEncoderByXsdType($type);
        }

        // Try to find a match for the extended simple type:
        // Or fallback to the default scalar encoder.
        return $meta->extends()
            ->filter(static fn ($extend): bool => $extend['isSimple'] ?? false)
            ->map(static fn ($extends) : XmlEncoder => $context->registry->findSimpleEncoderByNamespaceName(
                $extends['namespace'],
                $extends['type'],
            ))
            ->unwrapOr(ScalarTypeEncoder::default());
    }

    private function detectIsListType(XsdType $type): bool
    {
        $meta = $type->getMeta();

        // Repeating elements will be decorated inside the regular EncoderDetector.
        // There is no need to add a list encoder for repeating elements.
        if ($meta->isRepeatingElement()->unwrapOr(false)) {
            return false;
        }

        if ($meta->isList()->unwrapOr(false)) {
            return true;
        }

        $unions = $meta->unions();
        $unionsContainsList = $unions
            ->map(static fn (array $unionList): bool => any(
                $unionList,
                static fn (array $union): bool => $union['isList']
            ))
            ->unwrapOr(false);
        if ($unionsContainsList) {
            return true;
        }

        return false;
    }
}
