<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Closure;
use Generator;
use Soap\Engine\Metadata\Model\MethodMeta;
use Soap\WsdlReader\Model\Definitions\BindingStyle;
use Soap\WsdlReader\Model\Definitions\Namespaces;
use XMLWriter;
use function VeeWee\Xml\Writer\Builder\children;
use function VeeWee\Xml\Writer\Builder\namespaced_element;

final class OperationBuilder
{
    /**
     * @param list<Closure(XMLWriter): Generator<bool>> $parameters
     */
    public function __construct(
        private readonly MethodMeta $meta,
        private readonly Namespaces $namespaces,
        private readonly array $parameters
    ) {
    }

    /**
     * @return Generator<bool>
     */
    public function __invoke(XMLWriter $writer): Generator
    {
        $bindingStyle = BindingStyle::tryFrom($this->meta->bindingStyle()->unwrapOr(BindingStyle::DOCUMENT->value));

        yield from match($bindingStyle) {
            BindingStyle::DOCUMENT => $this->buildDocument($writer),
            BindingStyle::RPC => $this->buildRpc($writer),
        };
    }

    /**
     * @return Generator<bool>
     */
    private function buildDocument(XMLWriter $writer): Generator
    {
        yield from children($this->parameters)($writer);
    }

    /**
     * @return Generator<bool>
     */
    private function buildRpc(XMLWriter $writer): Generator
    {
        $operationName = $this->meta->operationName()->unwrap();
        $namespace = $this->meta->inputNamespace()->or($this->meta->targetNamespace())->unwrap();

        yield from namespaced_element(
            $namespace,
            $this->namespaces->lookupNameFromNamespace($namespace)->unwrapOr('tns'),
            $operationName,
            children($this->parameters),
        )($writer);
    }
}
