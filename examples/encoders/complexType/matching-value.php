<?php declare(strict_types=1);

require_once \dirname(__DIR__, 3) . '/vendor/autoload.php';

use Soap\Encoding\Encoder;
use Soap\Encoding\EncoderRegistry;
use Soap\Encoding\Test\PhpCompatibility\Implied\ImpliedSchema015A;
use Soap\Encoding\Test\PhpCompatibility\Implied\ImpliedSchema015B;

/**
 * Sometimes, your XSD schema tell you to use any implementation for a certain base type.
 * When building the SOAP payload, you want control over what child object you want to use.
 * This encoder can be customized to encoder and context to use for any PHP.
 * This encoder works together with the XsiTypeEncoder to decode back from xsi:type attributes.
 *
 * Example:
 * <complexType name="A">
 *     <sequence>
 *         <element name="foo" type="xsd:string" />
 *     </sequence>
 * </complexType>
 * <complexType name="B">
 *     <complexContent>
 *     <extension base="tns:A">
 *         <sequence>
 *             <element name="bar" type="xsd:string" />
 *         </sequence>
 *     </extension>
 * </complexContent>
 * </complexType>
 *     <element name="return">
 *     <complexType>
 *         <sequence>
 *             <element name="responses" type="tns:A" minOccurs="0" maxOccurs="unbounded" />
 *         </sequence>
 *     </complexType>
 * </element>
 *
 *
 * The result looks like this:
 *
 * <testParam xsi:type="tns:return">
 *     <responses xsi:type="tns:A">
 *         <foo xsi:type="xsd:string">abc</foo>
 *     </responses>
 *     <responses xsi:type="tns:B">
 *         <foo xsi:type="xsd:string">def</foo>
 *         <bar xsi:type="xsd:string">ghi</bar>
 *     </responses>
 * </testParam>
 *
 * <=>
 *
 * ^ {#2507
 * +"responses": array:2 [
 * 0 => A^ {#2501
 * +foo: "abc"
 * }
 * 1 => B {#2504
 * +foo: "def"
 * +bar: "ghi"
 * }
 * ]
 * }
 */

EncoderRegistry::default()
    ->addClassMap('http://test-uri/', 'B', ImpliedSchema015B::class)
    ->addComplexTypeConverter('http://test-uri/', 'A', new Encoder\MatchingValueEncoder(
        encoderDetector: static fn (Encoder\Context $context, mixed $value): array =>
            $value instanceof ImpliedSchema015B
                ? [
                    $context->withType($context->type->copy('B')->withXmlTypeName('B')),
                    new Encoder\ObjectEncoder(ImpliedSchema015B::class),
                ]
                : [$context],
        defaultEncoder: new Encoder\ObjectEncoder(ImpliedSchema015A::class)
    ))
    // Alternative for using stdObjects only:
    ->addComplexTypeConverter('http://test-uri/', 'A', new Encoder\MatchingValueEncoder(
        encoderDetector: static fn (Encoder\Context $context, mixed $value): Encoder\Context => $context->withType(
            property_exists($value, 'bar')
                ? $context->type->copy('B')->withXmlTypeName('B')
                : $context->type
        ),
        defaultEncoder: new Encoder\ObjectEncoder(stdClass::class)
    ));
