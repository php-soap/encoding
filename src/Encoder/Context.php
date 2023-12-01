<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Encoding\EncoderRegistry;
use Soap\Engine\Metadata\Metadata;
use Soap\Engine\Metadata\Model\XsdType;

final class Context
{
    public function __construct(
        public /*readonly*/ XsdType $type,
        public /*readonly*/ Metadata $metadata,
        public /*readonly*/ EncoderRegistry $registry,
    ) {
    }

    public function withType(XsdType $type): self
    {
        $new = clone $this;
        $new->type = $type;

        return $new;
    }
}
