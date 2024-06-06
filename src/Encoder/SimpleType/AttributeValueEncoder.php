<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Exception\RestrictionException;
use VeeWee\Reflecta\Iso\Iso;
use function Psl\Type\scalar;

/**
 * @implements XmlEncoder<mixed, string|null>
 */
final class AttributeValueEncoder implements XmlEncoder
{
    /**
     * @param XmlEncoder<mixed, string> $typeEncoder
     */
    public function __construct(
        private readonly XmlEncoder $typeEncoder
    ) {
    }

    /**
     * @return Iso<mixed, string|null>
     */
    public function iso(Context $context): Iso
    {
        return (new Iso(
            fn (mixed $value): ?string => $this->to($context, $value),
            fn (?string $value): mixed => $this->from($context, $value),
        ));
    }

    public function to(Context $context, mixed $value): ?string
    {
        $meta = $context->type->getMeta();
        $fixed = $meta->fixed()
            ->map(fn (string $fixed): mixed => $this->typeEncoder->iso($context)->from($fixed))
            ->unwrapOr(null);

        if ($fixed !== null && $value !== $fixed) {
            throw RestrictionException::invalidFixedValue(
                scalar()->assert($fixed),
                scalar()->assert($value)
            );
        }

        return $value !== null ? $this->typeEncoder->iso($context)->to($value) : null;
    }

    public function from(Context $context, ?string $value): mixed
    {
        if ($value !== null) {
            return $this->typeEncoder->iso($context)->from($value);
        }

        $meta = $context->type->getMeta();
        $default = $meta->fixed()->or($meta->default())->unwrapOr(null);

        return $default !== null ? $this->typeEncoder->iso($context)->from($default) : null;
    }
}
