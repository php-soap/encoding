<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder\SoapEnc;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\SimpleType\ScalarTypeEncoder;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\TypeInference\XsiTypeDetector;
use Soap\Encoding\Xml\Writer\XsiAttributeBuilder;
use Soap\Encoding\Xml\XsdTypeXmlElementWriter;
use Soap\Engine\Metadata\Model\XsdType;
use VeeWee\Reflecta\Iso\Iso;
use function Psl\Vec\map;
use function VeeWee\Xml\Writer\Builder\children;
use function VeeWee\Xml\Writer\Builder\element;
use function VeeWee\Xml\Writer\Builder\value as buildValue;
use function VeeWee\Xml\Writer\Builder\namespaced_attribute as buildNamespacedAttribute;

/**
 * @template T
 *
 * @implements XmlEncoder<string, list<T>>
 */
final class SoapArrayEncoder implements XmlEncoder
{
    /**
     * @return Iso<string, list<T>>
     */
    public function iso(Context $context): Iso
    {
        return (new Iso(
            fn(array $value): string => $this->encodeArray($context, $value),
            fn(string $value): array => $this->decodeArray($context, $value),
        ));
    }

    /**
     * @param list<T> $data
     */
    private function encodeArray(Context $context, array $data): string
    {
        $type = $context->type;
        $meta = $type->getMeta();
        $itemNodeName = $meta->arrayNodeName()->unwrapOr('item');
        $itemType = $meta->arrayType()
            ->map(static fn (array $info): string => $info['itemType'])
            ->unwrapOr(XsiTypeDetector::detectFromValue(
                $context->withType(XsdType::any()),
                $data[0] ?? null
            ));

        return (new XsdTypeXmlElementWriter())(
            $context,
            children([
                buildNamespacedAttribute(
                    $type->getXmlNamespace(),
                    $type->getXmlNamespaceName(),
                    'arrayType',
                    $itemType . '[]'
                ),
                ...map(
                    $data,
                    static fn (mixed $value): \Closure => element(
                        $itemNodeName,
                        children([
                            (new XsiAttributeBuilder($context, $itemType)),
                            buildValue((new ScalarTypeEncoder())->iso($context)->to($value))
                        ])
                    )
                )
            ])
        );

    }

    private function decodeArray(Context $context, string $part): array
    {
        throw new \RuntimeException('Not implemented yet!');
    }
}


/**
 * <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://test-uri/"
 * xmlns:xsd="http://www.w3.org/2001/XMLSchema"
 * xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
 * xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 * SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
 * <SOAP-ENV:Body>
 * <ns1:test>
 * <testParam SOAP-ENC:arrayType="xsd:int[2]" xsi:type="ns1:testType">
 *  <item xsi:type="xsd:int">123</item>
 *  <item xsi:type="xsd:int">123</item>
 * </testParam>
 * </ns1:test>
 * </SOAP-ENV:Body>
 * </SOAP-ENV:Envelope>
 *
 * <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://test-uri/"
 * xmlns:xsd="http://www.w3.org/2001/XMLSchema"
 * xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
 * xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 * SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
 * <SOAP-ENV:Body>
 * <ns1:test>
     * <testParam SOAP-ENC:arrayType="xsd:int[2,1]" xsi:type="ns1:testType">
         * <item xsi:type="xsd:int">123</item>
         * <item xsi:type="xsd:int">123</item>
     * </testParam>
 * </ns1:test>
 * </SOAP-ENV:Body>
 * </SOAP-ENV:Envelope>
 */
