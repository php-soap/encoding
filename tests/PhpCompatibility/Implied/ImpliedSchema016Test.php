<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\PhpCompatibility\Implied;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Decoder;
use Soap\Encoding\Driver;
use Soap\Encoding\Encoder;
use Soap\Encoding\EncoderRegistry;
use Soap\Encoding\Test\PhpCompatibility\AbstractCompatibilityTests;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use stdClass;

/**
 * Tests MatchingValueEncoder + withBindingUse(ENCODED) in LITERAL mode
 * with qualified elements and simpleContent types (only unqualified attributes).
 *
 * Without the fix, duplicate xmlns attributes are produced on simpleContent types
 * like Amount (isAnyPropertyQualified=false), causing invalid XML.
 */
#[CoversClass(Driver::class)]
#[CoversClass(Encoder::class)]
#[CoversClass(Decoder::class)]
#[CoversClass(Encoder\MatchingValueEncoder::class)]
#[CoversClass(Encoder\ObjectEncoder::class)]
final class ImpliedSchema016Test extends AbstractCompatibilityTests
{
    protected string $style = 'document';
    protected string $use = 'literal';
    protected string $attributeFormDefault = 'elementFormDefault="qualified" attributeFormDefault="unqualified"';

    protected string $schema = <<<EOXML
    <!-- simpleContent: only unqualified attribute, no qualified elements -->
    <complexType name="Amount">
        <simpleContent>
            <extension base="xsd:decimal">
                <attribute name="currencyCode" type="xsd:string" use="required" />
            </extension>
        </simpleContent>
    </complexType>
    <complexType name="BaseModule" abstract="true">
        <sequence>
            <element name="position" type="xsd:int" minOccurs="0" />
        </sequence>
    </complexType>
    <complexType name="CostModule">
        <complexContent>
            <extension base="tns:BaseModule">
                <sequence>
                    <element name="amount" type="tns:Amount" minOccurs="0" />
                </sequence>
            </extension>
        </complexContent>
    </complexType>
    <complexType name="ModuleSpecialization">
        <sequence>
            <element name="module" type="tns:BaseModule" minOccurs="0" />
            <element name="replacement" type="xsd:boolean" />
        </sequence>
    </complexType>
    EOXML;
    protected string $type = 'type="tns:ModuleSpecialization"';

    protected function calculateParam(): mixed
    {
        return (object) [
            'module' => new ImpliedSchema016CostModule(
                position: 99,
                amount: (object) ['_' => 25.0, 'currencyCode' => 'EUR'],
            ),
            'replacement' => false,
        ];
    }

    protected function expectDecoded(): mixed
    {
        return (object) [
            'module' => new ImpliedSchema016CostModule(
                position: 99,
                amount: (object) ['_' => 25.0, 'currencyCode' => 'EUR'],
            ),
            'replacement' => false,
        ];
    }

    protected function registry(): EncoderRegistry
    {
        return parent::registry()
            ->addClassMap('http://test-uri/', 'CostModule', ImpliedSchema016CostModule::class)
            ->addComplexTypeConverter(
                'http://test-uri/',
                'BaseModule',
                new Encoder\MatchingValueEncoder(
                    encoderDetector: static fn (Encoder\Context $context, mixed $value): array =>
                        $value instanceof ImpliedSchema016CostModule
                            ? [
                                $context
                                    ->withType($context->type->copy('CostModule')->withXmlTypeName('CostModule'))
                                    ->withBindingUse(BindingUse::ENCODED),
                                new Encoder\ObjectEncoder(ImpliedSchema016CostModule::class),
                            ]
                            : [$context],
                    defaultEncoder: new Encoder\ObjectEncoder(stdClass::class)
                )
            );
    }

    protected function expectXml(): string
    {
        return <<<XML
        <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
            <SOAP-ENV:Body xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
                <tns:ModuleSpecialization xmlns:tns="http://test-uri/">
                    <tns:module xsi:type="tns:CostModule"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xmlns:tns="http://test-uri/">
                        <tns:position xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                            xsi:type="xsd:int"
                            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                            xmlns:tns="http://test-uri/">99</tns:position>
                        <tns:amount xsi:type="tns:Amount"
                            currencyCode="EUR"
                            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                            xmlns:tns="http://test-uri/">25</tns:amount>
                    </tns:module>
                    <tns:replacement xmlns:tns="http://test-uri/">false</tns:replacement>
                </tns:ModuleSpecialization>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
        XML;
    }
}

final class ImpliedSchema016CostModule
{
    public function __construct(
        public int $position,
        public ?object $amount = null,
    ) {
    }
}
