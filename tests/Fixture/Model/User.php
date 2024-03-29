<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Fixture\Model;

class User {
    public function __construct(
        public bool $active,
        public Hat $hat
    ) {
    }
}
