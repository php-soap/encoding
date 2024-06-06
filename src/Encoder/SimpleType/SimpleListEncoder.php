<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SimpleType;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\Feature;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Restriction\WhitespaceRestriction;
use Soap\Engine\Metadata\Model\TypeMeta;
use VeeWee\Reflecta\Iso\Iso;
use function Psl\Vec\filter;
use function Psl\Vec\map;

/**
 * @implements XmlEncoder<array, string>
 */
final class SimpleListEncoder implements Feature\ListAware, XmlEncoder
{
    /**
     * @param XmlEncoder<mixed, string> $typeEncoder
     */
    public function __construct(
        private readonly XmlEncoder $typeEncoder
    ) {
    }

    /**
     * @psalm-suppress ImplementedReturnTypeMismatch - ISO<S,A> does not ISO<S,T,A,B>
     *
     * @return Iso<string|array, string>
     */
    public function iso(Context $context): Iso
    {
        $type = $context->type;
        $innerIso = $this->typeEncoder->iso(
            $context->withType(
                $type->withMeta(static fn (TypeMeta $meta): TypeMeta => $meta->withIsList(false))
            )
        );

        return new Iso(
            static fn (string|array $value): string => match(true) {
                is_string($value) => $value,
                default => implode(' ', map(
                    $value,
                    static fn (mixed $item): string => $innerIso->to($item),
                ))
            },
            static fn (string $value): array => map(
                filter(
                    explode(' ', WhitespaceRestriction::collapse($value)),
                    static fn (string $value): bool => $value !== '',
                ),
                static fn (string $item): mixed => $innerIso->from($item),
            )
        );
    }
}
