<?php declare(strict_types=1);

require_once \dirname(__DIR__, 3) . '/vendor/autoload.php';

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\EncoderRegistry;
use Soap\Encoding\Xml\Node\Element;
use VeeWee\Reflecta\Iso\Iso;
use function VeeWee\Xml\Encoding\document_encode;
use function VeeWee\Xml\Encoding\xml_decode;

/**
 * This encoder can deal with dynamic XML element structures:
 *
 * <complexType name="yourTypeUsingTheAnyType">
 *   <sequence>
 *     <any processContents="lax" />
 *   </sequence>
 * </complexType>
 *
 * This encoder will use veewee/xml to encode and decode the whole XML structure so that it can be used by you.
 *
 * The result looks like this:
 *
 * <customerData>
 *   <foo />
 *   <bar />
 *   <hello>world</hello>
 * </customerData>
 *
 * <=>
 *
 * ^ {#1761
 *   +"customerName": "John Doe"
 *   +"customerEmail": "john@doe.com"
 *   +"customerData": array:3 [
 *     "foo" => ""
 *     "bar" => ""
 *     "hello" => "world"
 *   ]
 * }
 */

EncoderRegistry::default()
    ->addComplexTypeConverter(
        'http://yournamespace',
        'yourTypeUsingTheAnyType',
        new class implements XmlEncoder {
            /**
             * @return Iso<array, string>
             */
            public function iso(Context $context): Iso
            {
                $typeName = $context->type->getName();

                return new Iso(
                    to: static fn (array $data): string => document_encode([$typeName => $data])->stringifyDocumentElement(),
                    from: static fn (Element|string $xml): array => xml_decode(
                        ($xml instanceof Element ? $xml : Element::fromString($xml))->value()
                    )[$typeName],
                );
            }
        }
    );
