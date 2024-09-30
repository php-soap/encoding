<?php declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\Feature;

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
use function VeeWee\Xml\Encoding\document_encode;
use function VeeWee\Xml\Encoding\xml_decode;

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
                            to: static fn (array $data): string => document_encode([$typeName => $data])->stringifyDocumentElement(),
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
