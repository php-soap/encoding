<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\Exception\EncodingException;
use Throwable;
use VeeWee\Reflecta\Iso\Iso;

/**
 * @template I
 * @template O
 *
 * @implements XmlEncoder<I, O>
 *
 */
final class ErrorHandlingEncoder implements XmlEncoder
{
    /**
     * @param XmlEncoder<I, O> $encoder
     */
    public function __construct(
        private readonly XmlEncoder $encoder
    ) {
    }

    public function iso(Context $context): Iso
    {
        $meta = $context->type->getMeta();
        $path = $meta->isSimple()
            ->filter(static fn (bool $isSimple): bool => !$isSimple)
            ->unwrapOr($context->type->getXmlTargetNodeName());
        $path = $meta->isAttribute()
            ->map(static fn (bool $isAttribute): string => '@' . $path)
            ->unwrapOr($path);
        $innerIso = $this->encoder->iso($context);

        return new Iso(
            static function (mixed $value) use ($innerIso, $context, $path): mixed {
                try {
                    return $innerIso->to($value);
                } catch (Throwable $exception) {
                    throw EncodingException::encodingValue($value, $context->type, $exception, $path);
                }
            },
            static function (mixed $value) use ($innerIso, $context, $path): mixed {
                try {
                    return $innerIso->from($value);
                } catch (Throwable $exception) {
                    throw EncodingException::decodingValue($value, $context->type, $exception, $path);
                }
            }
        );
    }
}
