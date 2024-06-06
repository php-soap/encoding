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
        $innerIso = $this->encoder->iso($context);
        $buildPath = static function() use ($context): ?string {
            $meta = $context->type->getMeta();
            $isElement = $meta->isElement()->unwrapOr(false);
            $isAttribute = $meta->isAttribute()->unwrapOr(false);
            if (!$isElement && !$isAttribute) {
                return null;
            }

            $path = $context->type->getXmlTargetNodeName();
            if ($isAttribute) {
                return '@' . $path;
            }

            return $path;
        };

        return new Iso(
            static function (mixed $value) use ($innerIso, $context, $buildPath): mixed {
                try {
                    return $innerIso->to($value);
                } catch (Throwable $exception) {
                    throw EncodingException::encodingValue($value, $context->type, $exception, $buildPath());
                }
            },
            static function (mixed $value) use ($innerIso, $context, $buildPath): mixed {
                try {
                    return $innerIso->from($value);
                } catch (Throwable $exception) {
                    throw EncodingException::decodingValue($value, $context->type, $exception, $buildPath());
                }
            }
        );
    }
}
