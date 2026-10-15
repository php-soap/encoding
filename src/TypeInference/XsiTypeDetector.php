<?php
declare(strict_types=1);

namespace Soap\Encoding\TypeInference;

use Dom\Element;
use Psl\Option\Option;
use Soap\Encoding\Cache\ScopedCache;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\FixedIsoEncoder;
use Soap\Encoding\EncoderRegistry;
use Soap\Encoding\Xml\Node\Element as XmlElement;
use Soap\Encoding\Xml\Node\ElementList;
use Soap\Engine\Metadata\Model\TypeMeta;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\WsdlReader\Parser\Xml\QnameParser;
use Soap\Xml\Xmlns as SoapXmlns;
use VeeWee\Xml\Xmlns\Xmlns;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function Psl\Option\none;
use function Psl\Option\some;
use function sprintf;

final class XsiTypeDetector
{
    /**
     * @return ScopedCache<EncoderRegistry, FixedIsoEncoder<mixed, mixed, string|null, XmlElement|ElementList|string|null>>
     *
     * @psalm-suppress LessSpecificReturnStatement, MoreSpecificReturnType, MixedReturnStatement
     */
    private static function cache(): ScopedCache
    {
        static $cache = new ScopedCache();

        return $cache;
    }

    /**
     * @psalm-param mixed $value
     */
    public static function detectFromValue(Context $context, mixed $value): string
    {
        return self::detectFromContext($context)->unwrapOrElse(
            static function () use ($context, $value) {
                $xsd = $context->namespaces->lookupNameFromNamespace(SoapXmlns::xsd()->value())->unwrap();

                return match (true) {
                    is_string($value) => $xsd . ':string',
                    is_int($value) => $xsd . ':int',
                    is_float($value) => $xsd . ':float',
                    is_bool($value) => $xsd . ':boolean',
                    default => $xsd . ':anyType',
                };
            }
        );
    }

    /**
     * @return Option<XsdType>
     */
    public static function detectXsdTypeFromXmlElement(Context $context, Element $element): Option
    {
        $qname = self::detectQNameFromXmlElement($context, $element);
        if ($qname === null) {
            return none();
        }

        return some(self::createXsdType($context, $qname[0], $qname[1]));
    }

    /**
     * @return Option<FixedIsoEncoder<mixed, mixed, string|null, XmlElement|ElementList|string|null>>
     */
    public static function detectEncoderFromXmlElement(Context $context, Element $element): Option
    {
        $qname = self::detectQNameFromXmlElement($context, $element);
        if ($qname === null) {
            return none();
        }

        [$namespaceUri, $localName] = $qname;

        return some(
            self::cache()->lookup(
                $context->registry,
                self::cacheKey($namespaceUri, $localName, $context->type->getMeta()),
                static function () use ($context, $namespaceUri, $localName): FixedIsoEncoder {
                    $type = self::withResolvedSimpleness($context, self::createXsdType($context, $namespaceUri, $localName));
                    $normalizedMeta = $type->getMeta()
                        ->withIsNullable(false)
                        ->withIsRepeatingElement(false);
                    $detectorContext = $context
                        ->withType($type->withMeta(static fn () => $normalizedMeta))
                        ->withSkipXsiTypeDetection(true);

                    return new FixedIsoEncoder(
                        $context->registry->detectEncoderForContext($detectorContext)->iso(
                            $context->withType($type)
                        )
                    );
                }
            )
        );
    }

    /**
     * @return array{non-empty-string, non-empty-string}|null
     */
    private static function detectQNameFromXmlElement(Context $context, Element $element): ?array
    {
        $xsiType = $element->getAttributeNS(Xmlns::xsi()->value(), 'type') ?? $element->getAttribute('xsi:type');
        if ($xsiType === null || $xsiType === '') {
            return null;
        }

        [$prefix, $localName] = (new QnameParser)($xsiType);
        if (!$localName) {
            return null;
        }

        $namespaceUri = $prefix
            ? $element->lookupNamespaceURI($prefix) ?? $context->namespaces->lookupNamespaceFromName($prefix)->unwrapOr(null)
            : $element->lookupNamespaceURI(null) ?? $element->namespaceURI;

        if ($namespaceUri === null || $namespaceUri === '') {
            return null;
        }

        return [$namespaceUri, $localName];
    }

    /**
     * We create a new type based on the detected xsi:type, but we keep the meta information of the original type.
     * This way we can still detect if the type is nullable, a union, used on an element, ...
     */
    private static function createXsdType(Context $context, string $namespaceUri, string $localName): XsdType
    {
        return $context->type
            ->copy($localName)
            ->withXmlTypeName($localName)
            ->withXmlNamespace($namespaceUri);
    }

    private static function cacheKey(string $namespaceUri, string $localName, TypeMeta $meta): string
    {
        return $namespaceUri . '|' . $localName
            . '|' . ($meta->isElement()->unwrapOr(false) ? 'e' : '')
            . ($meta->isAttribute()->unwrapOr(false) ? 'a' : '')
            . ($meta->isNullable()->unwrapOr(false) ? 'n' : '')
            . ($meta->isList()->unwrapOr(false) ? 'l' : '')
            . ($meta->isQualified()->unwrapOr(false) ? 'q' : '')
            . $meta->isSimple()->map(static fn (bool $isSimple): string => $isSimple ? 's' : 'c')->unwrapOr('');
    }

    /**
     * The detected type inherits the meta of the slot it was found in.
     * Whether it is a simple or a complex type depends on the xsi:type itself though:
     * an xsd:anyType slot can hold a struct and a complex slot can hold an xsd:string.
     * An xsi:type that restates the declared type keeps the slot meta, which matters for wildcards like xsd:any.
     */
    private static function withResolvedSimpleness(Context $context, XsdType $type): XsdType
    {
        $slotType = $context->type;
        if ($slotType->getXmlNamespace() === $type->getXmlNamespace() && $slotType->getXmlTypeName() === $type->getXmlTypeName()) {
            return $type;
        }

        $registry = $context->registry;
        $isSimple = match (true) {
            $registry->hasRegisteredComplexTypeForXsdType($type) => false,
            $registry->hasRegisteredSimpleTypeForXsdType($type) => true,
            default => $context->metadata->getTypes()
                ->findByNameAndXmlNamespace($type->getXmlTypeName(), $type->getXmlNamespace())
                ?->getXsdType()
                ->getMeta()
                ->isSimple()
                ->unwrapOr(false),
        };

        return match ($isSimple) {
            null => $type,
            true => $type->withMeta(static fn (TypeMeta $meta): TypeMeta => $meta->withIsSimple(true)->withIsElement(true)),
            false => $type->withMeta(static fn (TypeMeta $meta): TypeMeta => $meta->withIsSimple(false)),
        };
    }

    /**
     * @return Option<non-empty-string>
     */
    private static function detectFromContext(Context $context): Option
    {
        $type = $context->type;
        $isAny = $type->getXmlNamespace() === SoapXmlns::xsd()->value() && $type->getName() === 'anyType';
        $isUnion = $type->getMeta()->unions()->isSome();

        if ($isAny && !$isUnion) {
            return none();
        }

        return some(
            sprintf(
                '%s:%s',
                $context->namespaces->lookupNameFromNamespace($type->getXmlNamespace())->unwrap(),
                $type->getXmlTypeName()
            )
        );
    }
}
