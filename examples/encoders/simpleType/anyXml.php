<?php declare(strict_types=1);
require_once \dirname(__DIR__, 3) . '/vendor/autoload.php';

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\Feature\ElementAware;
use Soap\Encoding\Encoder\SimpleType\ScalarTypeEncoder;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\EncoderRegistry;
use VeeWee\Reflecta\Iso\Iso;
use function VeeWee\Xml\Encoding\document_encode;
use function VeeWee\Xml\Encoding\xml_decode;

/**
 * Most of the time, you don't need access to the wrapping XML element from within a simple type encoder.
 * Sometimes, when using anyXml or anyType, you might want to have full control over the wrapping element.
 * This allows you to use 3rd party tools to build the full XML structure from within a simple type encoder.
 *
 * Do note that:
 * - You'll need to check if the current provided type is an attribute or not.
 * - If you want to add xsi:type information, you need to add / parse it manually.
 * - The result will be used as a raw XML input, meaning it should be valid XML (without the header declearations).
 */
EncoderRegistry::default()
    ->addSimpleTypeConverter(
        'http://www.w3.org/2001/XMLSchema',
        'anyXml',
        new class implements
            ElementAware,
            XmlEncoder {
            public function iso(Context $context): Iso
            {
                if ($context->type->getMeta()->isAttribute()->unwrapOr(false)) {
                    return (new ScalarTypeEncoder())->iso($context);
                }

                $targetElementName = $context->type->getXmlTargetNodeName();
                return new Iso(
                    to: static fn (array $data): string => document_encode([$targetElementName => $data])
                        ->manipulate(static fn (\DOMDocument $document) => $document->documentElement->setAttributeNS(
                            VeeWee\Xml\Xmlns\Xmlns::xsi()->value(),
                            'xsi:type',
                            'custom:type'
                        ))
                        ->stringifyDocumentElement(),
                    from: static fn (string $xml): array => xml_decode($xml)[$targetElementName],
                );
            }
        }
    );
