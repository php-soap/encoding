<?php
declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Soap\Engine\Metadata\Model\XsdType;

final class Context
{
    public function __construct(
        public readonly XsdType $type,
    ) {
    }
}
