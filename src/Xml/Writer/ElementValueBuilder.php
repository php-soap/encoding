<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Closure;
use Generator;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\Feature;
use Soap\Encoding\Encoder\XmlEncoder;
use VeeWee\Reflecta\Iso\IsoInterface;
use XMLWriter;
use function VeeWee\Xml\Writer\Builder\cdata;
use function VeeWee\Xml\Writer\Builder\children;
use function VeeWee\Xml\Writer\Builder\value;

final class ElementValueBuilder
{
    /**
     * @param list<Closure(XMLWriter): Generator<bool>> $builders
     */
    private function __construct(
        private readonly array $builders,
    ) {
    }

    /**
     * @param XmlEncoder<mixed, mixed, string, mixed> $encoder
     * @psalm-param mixed $value
     */
    public static function fromEncoder(Context $context, XmlEncoder $encoder, mixed $value): self
    {
        return new self([
            XsiAttributeBuilder::forEncodedValue($context, $encoder, $value),
            self::valueWriter($encoder, static fn (): string => $encoder->iso($context)->to($value)),
        ]);
    }

    /**
     * @param XmlEncoder<mixed, mixed, mixed, mixed> $encoder
     * @param IsoInterface<mixed, mixed, string, mixed> $iso
     * @psalm-param mixed $value
     */
    public static function fromIso(Context $context, XmlEncoder $encoder, IsoInterface $iso, mixed $value): self
    {
        return new self([
            XsiAttributeBuilder::forEncodedValue($context, $encoder, $value),
            self::valueWriter($encoder, static fn (): string => $iso->to($value)),
        ]);
    }

    /**
     * @return Generator<bool>
     */
    public function __invoke(XMLWriter $writer): Generator
    {
        yield from children($this->builders)($writer);
    }

    /**
     * @param XmlEncoder<mixed, mixed, mixed, mixed> $encoder
     * @param Closure(): string $valueProvider
     *
     * @return Closure(XMLWriter): Generator<bool>
     */
    private static function valueWriter(XmlEncoder $encoder, Closure $valueProvider): Closure
    {
        $isCData = $encoder instanceof Feature\CData;

        return static function (XMLWriter $writer) use ($isCData, $valueProvider): Generator {
            $encoded = $valueProvider();
            $builder = $isCData ? cdata(value($encoded)) : value($encoded);

            yield from $builder($writer);
        };
    }
}
