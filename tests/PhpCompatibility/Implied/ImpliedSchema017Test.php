<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\PhpCompatibility\Implied;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Decoder;
use Soap\Encoding\Driver;
use Soap\Encoding\Encoder;
use Soap\Encoding\Test\PhpCompatibility\AbstractCompatibilityTests;

/**
 * A repeating group reference around a choice (xsd-reader fix): both choice
 * members become optional, unbounded list elements on the referencing type.
 * On the wire that is repeated sibling elements - this proves the encoder
 * writes one element per list item and the decoder collects them back into
 * arrays, even when the two branches are interleaved.
 */
#[CoversClass(Driver::class)]
#[CoversClass(Encoder::class)]
#[CoversClass(Decoder::class)]
#[CoversClass(Encoder\RepeatingElementEncoder::class)]
#[CoversClass(Encoder\ObjectEncoder::class)]
final class ImpliedSchema017Test extends AbstractCompatibilityTests
{
    protected string $style = 'document';
    protected string $use = 'literal';
    protected string $attributeFormDefault = 'elementFormDefault="qualified"';

    protected string $schema = <<<EOXML
    <group name="Operation">
        <choice>
            <element name="delete" type="xsd:string" />
            <element name="write" type="xsd:string" />
        </choice>
    </group>
    <complexType name="Message">
        <sequence>
            <element name="transactionId" type="xsd:string" minOccurs="0" />
            <group ref="tns:Operation" minOccurs="0" maxOccurs="unbounded" />
        </sequence>
    </complexType>
    EOXML;
    protected string $type = 'type="tns:Message"';

    protected function calculateParam(): mixed
    {
        return (object) [
            'transactionId' => 'TX1',
            'delete' => ['A', 'B'],
            'write' => ['C'],
        ];
    }

    protected function expectXml(): string
    {
        return <<<XML
        <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
            <SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
                <tns:Message xmlns:tns="http://test-uri/">
                    <tns:transactionId xmlns:tns="http://test-uri/">TX1</tns:transactionId>
                    <tns:delete xmlns:tns="http://test-uri/">A</tns:delete>
                    <tns:delete xmlns:tns="http://test-uri/">B</tns:delete>
                    <tns:write xmlns:tns="http://test-uri/">C</tns:write>
                </tns:Message>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
        XML;
    }
}
