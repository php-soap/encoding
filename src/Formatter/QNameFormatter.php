<?php
declare(strict_types=1);

namespace Soap\Encoding\Formatter;

use Soap\Xml\Xmlns;

final class QNameFormatter
{
    public function __invoke(string $namespace, string $name): string
    {
        return '{'.$namespace.':'.mb_strtolower($name).'}';
    }
}
