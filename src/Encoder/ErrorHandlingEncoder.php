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
 * @implements Feature\DecoratingEncoder<TData, TXml>
 */
final class ErrorHandlingEncoder implements Feature\DecoratingEncoder, XmlEncoder
{
    /**
     * @param XmlEncoder<TData, TXml> $encoder
     */
    public function __construct(
        private readonly XmlEncoder $encoder
    ) {
    }

    /**
     * @return XmlEncoder<TData, TXml>
     */
    public function decoratedEncoder(): XmlEncoder
    {
        return $this->encoder;
    }

    /**
     * @return Iso<TData, TXml>
     */
    public function iso(Context $context): Iso
    {
        $innerIso = $this->encoder->iso($context);

        return new Iso(
            /**
             * @psalm-param TData $value
             * @psalm-return TXml
             */
            static function (mixed $value) use ($innerIso, $context): mixed {
                try {
                    return $innerIso->to($value);
                } catch (Throwable $exception) {
                    throw EncodingException::encodingValue($value, $context->type, $exception, self::buildPath($context));
                }
            },
            /**
             * @psalm-param TXml $value
             * @psalm-return TData
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
