<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Support\Throws;

final class ThrowFrame
{
    public function __construct(
        public readonly string $function,
        public readonly string $file,
        public readonly int $line,
    ) {
    }
}
