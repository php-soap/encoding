<?php declare(strict_types=1);

namespace Soap\Encoding\Encoder\Method;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\EncoderRegistry;
use Soap\Engine\Metadata\Metadata;
use Soap\Engine\Metadata\Model\Method;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Model\Definitions\Namespaces;

final class MethodContext
{
    public function __construct(
        public readonly Method $method,
        public readonly Metadata $metadata,
        public readonly EncoderRegistry $registry,
        public readonly Namespaces $namespaces,
        public readonly BindingUse $bindingUse = BindingUse::LITERAL,
    ) {
    }

    public function createXmlEncoderContextForType(XsdType $type): Context
    {
        return new Context(
            $type,
            $this->metadata,
            $this->registry,
            $this->namespaces,
            $this->bindingUse
        );
    }

    public function withBindingUse(BindingUse $bindingUse): self
    {
        return new self(
            $this->method,
            $this->metadata,
            $this->registry,
            $this->namespaces,
            $bindingUse
        );
    }
}
