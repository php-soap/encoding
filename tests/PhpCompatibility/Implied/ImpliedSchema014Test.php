<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\PhpCompatibility\Implied;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Decoder;
use Soap\Encoding\Driver;
use Soap\Encoding\Encoder;
use Soap\Encoding\EncoderRegistry;
use Soap\Encoding\Test\PhpCompatibility\AbstractCompatibilityTests;
use stdClass;

#[CoversClass(Driver::class)]
#[CoversClass(Encoder::class)]
#[CoversClass(Decoder::class)]
#[CoversClass(Encoder\SoapEnc\SoapArrayEncoder::class)]
#[CoversClass(Encoder\MatchingValueEncoder::class)]
#[CoversClass(Encoder\XsiTypeEncoder::class)]
final class ImpliedSchema014Test extends AbstractCompatibilityTests
{
    protected string $schema = <<<EOXML
    <complexType name="A">
        <sequence>
            <element name="foo" type="xsd:string" />
        </sequence>
    </complexType>
    <complexType name="B">
      <complexContent>
        <extension base="tns:A">
          <sequence>
            <element name="bar" type="xsd:string" />
          </sequence>
        </extension>
      </complexContent>
    </complexType>
    <element name="return">
        <complexType>
            <sequence>
                <element name="responses" type="tns:A" minOccurs="0" maxOccurs="unbounded" />
            </sequence>        
        </complexType>
    </element>
    EOXML;
    protected string $type = 'type="tns:return"';

    protected function calculateParam(): mixed
    {
        return (object)[
            'responses' => [
                (object)['foo' => 'abc'],
                (object)['foo' => 'def', 'bar' => 'ghi'],
            ],
        ];
    }

    protected function registry(): EncoderRegistry
    {
        return parent::registry()
            ->addComplexTypeConverter(
                'http://test-uri/',
                'A',
                new Encoder\MatchingValueEncoder(
                    encoderDetector: static fn (Encoder\Context $context, mixed $value): Encoder\Context => $context->withType(
                        property_exists($value, 'bar')
                            ? $context->type->copy('B')->withXmlTypeName('B')
                            : $context->type
                    ),
                    defaultEncoder: new Encoder\ObjectEncoder(stdClass::class)
                )
            );
    }

    protected function expectXml(): string
    {
        return <<<XML
         <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"
            xmlns:tns="http://test-uri/"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xmlns:xsd="http://www.w3.org/2001/XMLSchema"
            xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/">
            <SOAP-ENV:Body SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                <tns:test>
                  <testParam xsi:type="tns:return">
                    <responses xsi:type="tns:A">
                      <foo xsi:type="xsd:string">abc</foo>
                    </responses>
                    <responses xsi:type="tns:B">
                      <foo xsi:type="xsd:string">def</foo>
                      <bar xsi:type="xsd:string">ghi</bar>
                    </responses>
                  </testParam>
                </tns:test>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
        XML;
    }
}
