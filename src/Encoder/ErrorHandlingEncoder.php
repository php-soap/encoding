<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\Exception\EncodingException;
use Throwable;
use VeeWee\Reflecta\Iso\Iso;

/**
 * @template TDataIn
 * @template TDataOut
 * @template TXmlOut
 * @template TXmlIn
 *
 * @implements XmlEncoder<TDataIn, TDataOut, TXmlOut, TXmlIn>
 * @implements Feature\DecoratingEncoder<TDataIn, TDataOut, TXmlOut, TXmlIn>
 */
final class ErrorHandlingEncoder implements Feature\DecoratingEncoder, XmlEncoder
{
    /**
     * @param XmlEncoder<TDataIn, TDataOut, TXmlOut, TXmlIn> $encoder
     */
    public function __construct(
        private readonly XmlEncoder $encoder
    ) {
    }

    /**
     * @return XmlEncoder<TDataIn, TDataOut, TXmlOut, TXmlIn>
     */
    public function decoratedEncoder(): XmlEncoder
    {
        return $this->encoder;
    }

    /**
     * @return Iso<TDataIn, TDataOut, TXmlOut, TXmlIn>
     */
    public function iso(Context $context): Iso
    {
        $innerIso = $this->encoder->iso($context);

        return new Iso(
            /**
             * @psalm-param TDataIn $value
             * @psalm-return TXmlOut
             */
            static function (mixed $value) use ($innerIso, $context): mixed {
                try {
                    return $innerIso->to($value);
                } catch (Throwable $exception) {
                    throw EncodingException::encodingValue($value, $context->type, $exception, self::buildPath($context));
                }
            },
            /**
             * @psalm-param TXmlIn $value
             * @psalm-return TDataOut
             */
            static function (mixed $value) use ($innerIso, $context): mixed {
                try {
                    return $innerIso->from($value);
                } catch (Throwable $exception) {
                    throw EncodingException::decodingValue($value, $context->type, $exception, self::buildPath($context));
                }
            }
        );
    }

    private static function buildPath(Context $context): ?string
    {
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
    }
}
