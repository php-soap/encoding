<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use VeeWee\Reflecta\Iso\Iso;

/**
 * @implements XmlEncoder<string|null, mixed>
 */
final class AttributeValueEncoder implements XmlEncoder
{
    /**
     * @param XmlEncoder<string, mixed> $typeEncoder
     */
    public function __construct(
        private readonly XmlEncoder $typeEncoder
    ) {
    }

    /**
     * @param Context $context
     * @return Iso<string, mixed>
     */
    public function iso(Context $context): Iso
    {
        return (new Iso(
            fn(mixed $value): ?string => $this->to($context, $value),
            fn(?string $value): mixed => $this->from($context, $value),
        ));
    }

    public function to(Context $context, mixed $value): ?string
    {
        $meta = $context->type->getMeta();
        $fixed = $meta->fixed()
            ->map(fn(string $fixed): mixed => $this->typeEncoder->iso($context)->from($fixed))
            ->unwrapOr(null);

        if ($fixed !== null && $value !== $fixed) {
            // TODO custom exception
            throw new \RuntimeException(sprintf('Provided attribute value should be fixed to %s. Got %s', $fixed, $value));
        }

        return $value ? $this->typeEncoder->iso($context)->to($value) : null;
    }

    public function from(Context $context, ?string $value): mixed
    {
        if ($value !== null) {
            return $this->typeEncoder->iso($context)->from($value);
        }

        $meta = $context->type->getMeta();
        $default = $meta->fixed()->or($meta->default())->unwrapOr(null);

        return $default ? $this->typeEncoder->iso($context)->from($default) : null;
    }
}
