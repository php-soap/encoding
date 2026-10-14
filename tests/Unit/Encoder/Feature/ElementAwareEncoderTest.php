<?php declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\Feature;

use Closure;
use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\Feature\ElementAware;
use Soap\Encoding\Encoder\SimpleType\EncoderDetector;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\EncoderRegistry;
use Soap\Encoding\Test\Unit\Encoder\AbstractEncoderTests;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\Xml\Xmlns;
use VeeWee\Reflecta\Iso\Iso;
use VeeWee\Xml\Writer\Writer;
use function Psl\Dict\map_with_key;
use function VeeWee\Xml\Encoding\xml_decode;
use function VeeWee\Xml\Writer\Builder\children;
use function VeeWee\Xml\Writer\Builder\element;
use function VeeWee\Xml\Writer\Builder\value;
use function VeeWee\Xml\Writer\Mapper\memory_output;

#[CoversClass(EncoderDetector::class)]
final class ElementAwareEncoderTest extends AbstractEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $registry = EncoderRegistry::default()
            ->addSimpleTypeConverter(
                Xmlns::xsd()->value(),
                'anyType',
                new class implements
                    ElementAware,
                    XmlEncoder {
                    public function iso(Context $context): Iso
                    {
                        $typeName = $context->type->getXmlTargetNodeName();
                        return new Iso(
                            to: static fn (array $data): string => Writer::inMemory()
                                ->write(element($typeName, children(map_with_key(
                                    $data,
                                    static fn (string $key, string $value): Closure => element($key, value($value)),
                                ))))
                                ->map(memory_output()),
                            from: static fn (string $xml): array => xml_decode($xml)[$typeName],
                        );
                    }
                }
            );

        $context = self::createContext(
            XsdType::any()->withXmlTargetNodeName('data'),
            encoderRegistry: $registry
        );
        $encoder = $registry->detectEncoderForContext($context);

        yield 'element-aware-simple-type' => [
            'encoder' => $encoder,
            'context' => $context,
            'xml' => '<data><key>value</key></data>',
            'data' => ['key' => 'value'],
        ];
    }
}
