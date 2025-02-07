<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Reader;

use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Node\ElementList;
use Soap\Engine\Metadata\Model\MethodMeta;
use Soap\WsdlReader\Model\Definitions\BindingStyle;
use function Psl\Vec\map;
use function VeeWee\Xml\Dom\Assert\assert_element;
use function VeeWee\Xml\Dom\Locator\Element\children as locateChildElements;

final class OperationReader
{
    public function __construct(
        private readonly MethodMeta $meta,
    ) {
    }

    /**
     * Reads all operation response message parts:
     *
     * @param non-empty-string $xml
     */
    public function __invoke(string $xml): ElementList
    {
        $bindingStyle = BindingStyle::tryFrom($this->meta->bindingStyle()->unwrapOr(BindingStyle::DOCUMENT->value));

        // The Response can contain out of multiple response parts.
        // Therefore, it is being wrapped by a central root element:
        $body = (new SoapEnvelopeReader())($xml);
        $bodyElement = $body->element();

        $elements = match($bindingStyle) {
            BindingStyle::DOCUMENT => locateChildElements($bodyElement),
            BindingStyle::RPC => locateChildElements(assert_element($bodyElement->firstElementChild)),
        };

        return new ElementList(...map($elements, Element::fromDOMElement(...)));
    }
}
