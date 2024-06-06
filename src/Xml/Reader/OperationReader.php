<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Reader;

use DOMElement;
use Soap\Engine\Metadata\Model\MethodMeta;
use Soap\WsdlReader\Model\Definitions\BindingStyle;
use VeeWee\Xml\Dom\Document;
use function Psl\Vec\map;
use function VeeWee\Xml\Dom\Locator\Element\children as locateChildElements;
use function VeeWee\Xml\Dom\Xpath\Configurator\namespaces;

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
     * @return list<string>
     */
    public function __invoke(string $xml): array
    {
        $operationName = $this->meta->operationName()->unwrap();
        $namespace = $this->meta->outputNamespace()->or($this->meta->targetNamespace())->unwrap();
        $bindingStyle = BindingStyle::tryFrom($this->meta->bindingStyle()->unwrapOr(BindingStyle::DOCUMENT->value));

        // The Response can contain out of multiple response parts.
        // Therefore, it is being wrapped by a central root element:
        $body = Document::fromXmlString('<root>'.(new SoapEnvelopeReader())($xml).'</root>');
        $bodyElement = $body->locateDocumentElement();
        $xpath = $body->xpath(namespaces(['tns' => $namespace]));

        $elements = match($bindingStyle) {
            BindingStyle::DOCUMENT => locateChildElements($bodyElement),
            BindingStyle::RPC => locateChildElements(
                $xpath->querySingle('./tns:'.$operationName, $bodyElement)
            )
        };

        return map(
            $elements,
            static fn (DOMElement $element): string => Document::fromXmlNode($element)->stringifyDocumentElement(),
        );
    }
}
