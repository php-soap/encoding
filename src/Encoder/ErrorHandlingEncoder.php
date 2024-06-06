<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\Exception\EncodingException;
use Throwable;
use VeeWee\Reflecta\Iso\Iso;

/**
 * @template-covariant TData
 * @template-covariant TXml
 *
 * @implements XmlEncoder<TData, TXml>
 *
 */
final class ErrorHandlingEncoder implements XmlEncoder
{
    /**
     * @param XmlEncoder<TData, TXml> $encoder
     */
    public function __construct(
        private readonly XmlEncoder $encoder
    ) {
    }

    /**
     * @return Iso<TData, TXml>
     */
    public function iso(Context $context): Iso
    {
        $innerIso = $this->encoder->iso($context);
        $buildPath = static function () use ($context): ?string {
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
            /**
             * @psalm-param TData $value
             * @psalm-return TXml
             */
            static function (mixed $value) use ($innerIso, $context, $buildPath): mixed {
                try {
                    return $innerIso->to($value);
                } catch (Throwable $exception) {
                    throw EncodingException::encodingValue($value, $context->type, $exception, $buildPath());
                }
            },
            /**
             * @psalm-param TXml $value
             * @psalm-return TData
             */
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
