<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Fixture\Model;

final class Hat
{
    public function __construct(
        public string $color,
    ) {
    }
}
