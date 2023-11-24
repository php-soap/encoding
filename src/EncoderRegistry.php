<?php
declare(strict_types=1);

namespace Soap\Encoding;

use Psl\Collection\Map;
use Psl\Option\Option;
use Soap\Encoding\Encoder\Base64BinaryEncoder;
use Soap\Encoding\Encoder\IntEncoder;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\Encoder\StringEncoder;
use Soap\Encoding\Formatter\QNameFormatter;
use Soap\Xml\Xmlns;
use function Psl\Option\from_nullable;

final class EncoderRegistry
{
    /**
     * @param Map<string, XmlEncoder> $registry
     */
    private function __construct(
        private Map $registry
    ) {
    }

    public static function default(): self
    {
        $qNameFormatter = new QNameFormatter();
        $xsd = Xmlns::xsd()->value();
        return new self(new Map([
            // Strings:
            $qNameFormatter($xsd, 'string') => new StringEncoder(),
            $qNameFormatter($xsd, 'base64Binary') => new Base64BinaryEncoder(),

            // Integers:
            $qNameFormatter($xsd, 'int') => new IntEncoder(),
            $qNameFormatter($xsd, 'long') => new IntEncoder(),
            $qNameFormatter($xsd, 'short') => new IntEncoder(),
            $qNameFormatter($xsd, 'byte') => new IntEncoder(),
            $qNameFormatter($xsd, 'nonPositiveInteger') => new IntEncoder(),
            $qNameFormatter($xsd, 'positiveInteger') => new IntEncoder(),
            $qNameFormatter($xsd, 'nonNegativeInteger') => new IntEncoder(),
            $qNameFormatter($xsd, 'negativeInteger') => new IntEncoder(),
            $qNameFormatter($xsd, 'unsignedLong') => new IntEncoder(),
            $qNameFormatter($xsd, 'unsignedByte') => new IntEncoder(),
            $qNameFormatter($xsd, 'unsignedShort') => new IntEncoder(),
            $qNameFormatter($xsd, 'unsignedInt') => new IntEncoder(),
            $qNameFormatter($xsd, 'unsignedLong') => new IntEncoder(),
            $qNameFormatter($xsd, 'integer') => new IntEncoder(),

        ]));
    }

    /**
     * @param non-empty-string $namespace
     * @param non-empty-string $name
     * @return Option<XmlEncoder>
     */
    public function findByXsdType(string $namespace, string $name): Option
    {
        $qNameFormatter = new QNameFormatter();

        return from_nullable($this->registry->get($qNameFormatter($namespace, $name)));
    }
}
