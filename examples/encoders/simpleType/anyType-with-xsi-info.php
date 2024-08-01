<?php declare(strict_types=1);
require_once \dirname(__DIR__, 3) . '/vendor/autoload.php';

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\Feature\ElementContextEnhancer;
use Soap\Encoding\Encoder\SimpleType\ScalarTypeEncoder;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\EncoderRegistry;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use VeeWee\Reflecta\Iso\Iso;

/**
 * This encoder can add xsi:type information to the XML element on xsd:anyType simpleTypes on literal encoded documents.
 *
 * <xsd:element minOccurs="0" maxOccurs="1" name="value" type="xsd:anyType" />
 *
 * Will Result in for example:
 *
 * <value
 *   xmlns:xsd="http://www.w3.org/2001/XMLSchema"
 *   xsi:type="xsds:int"
 *   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 * >
 *  789
 * </value>
 */

EncoderRegistry::default()
    ->addSimpleTypeConverter(
        'http://www.w3.org/2001/XMLSchema',
        'anyType',
        new class implements
            ElementContextEnhancer,
            XmlEncoder {
            public function iso(Context $context): Iso
            {
                return (new ScalarTypeEncoder())->iso($context);
            }

            /**
             * This method allows to change the context on the wrapping elementEncoder.
             * By forcing the bindingUse to `ENCODED`, we can make sure the xsi:type attribute is added.
             */
            public function enhanceElementContext(Context $context): Context
            {
                return $context->withBindingUse(BindingUse::ENCODED);
            }
        }
    );
