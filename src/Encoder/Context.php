<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\EncoderRegistry;
use Soap\Engine\Metadata\Metadata;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Model\Definitions\Namespaces;

final class Context
{
    public function __construct(
        public readonly XsdType $type,
        public readonly Metadata $metadata,
        public readonly EncoderRegistry $registry,
        public readonly Namespaces $namespaces,
        public readonly BindingUse $bindingUse = BindingUse::LITERAL,
    ) {
    }

    public function withType(XsdType $type): self
    {
        return new self(
            $type,
            $this->metadata,
            $this->registry,
            $this->namespaces,
            $this->bindingUse,
        );
    }

    public function withBindingUse(BindingUse $bindingUse): self
    {
        return new self(
            $this->type,
            $this->metadata,
            $this->registry,
            $this->namespaces,
            $bindingUse,
        );
    }
}
