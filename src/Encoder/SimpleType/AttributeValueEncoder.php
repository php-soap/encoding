<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Exception\RestrictionException;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Reflecta\Iso\IsoInterface;
use function Psl\Type\scalar;

/**
 * @implements XmlEncoder<mixed, mixed, string|null, string|null>
 */
final class AttributeValueEncoder implements XmlEncoder
{
    /**
     * @param XmlEncoder<mixed, mixed, string, string> $typeEncoder
     */
    public function __construct(
        private readonly XmlEncoder $typeEncoder
    ) {
    }

    /**
     * @return Iso<mixed, mixed, string|null, string|null>
     */
    public function iso(Context $context): Iso
    {
        $typeIso = $this->typeEncoder->iso($context);

        return (new Iso(
            fn (mixed $value): ?string => $this->to($context, $typeIso, $value),
            fn (?string $value): mixed => $this->from($context, $typeIso, $value),
        ));
    }

    /**
     * @param IsoInterface<mixed, mixed, string, string> $typeIso
     */
    private function to(Context $context, IsoInterface $typeIso, mixed $value): ?string
    {
        $meta = $context->type->getMeta();
        $fixed = $meta->fixed()
            ->map(static fn (string $fixed): mixed => $typeIso->from($fixed))
            ->unwrapOr(null);

        if ($fixed !== null && $value !== $fixed) {
            throw RestrictionException::invalidFixedValue(
                scalar()->assert($fixed),
                scalar()->assert($value)
            );
        }

        return $value !== null ? $typeIso->to($value) : null;
    }

    /**
     * @param IsoInterface<mixed, mixed, string, string> $typeIso
     */
    private function from(Context $context, IsoInterface $typeIso, ?string $value): mixed
    {
        if ($value !== null) {
            return $typeIso->from($value);
        }

        $meta = $context->type->getMeta();
        $default = $meta->fixed()->or($meta->default())->unwrapOr(null);

        return $default !== null ? $typeIso->from($default) : null;
    }
}
