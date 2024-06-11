<?php
declare(strict_types=1);

namespace Soap\Encoding\Normalizer;

use function Psl\Type\non_empty_string;

final class PhpPropertyNameNormalizer
{
    public static function normalize(string $name): string
    {
        return self::camelCase($name, '{[^a-z0-9_]+}i');
    }

    /**
     * @param literal-string $regexp
     */
    private static function camelCase(string $word, string $regexp):string
    {
        $parts = array_filter(preg_split($regexp, $word));
        $keepUnchanged = array_shift($parts);
        $parts = array_map('ucfirst', $parts);
        array_unshift($parts, $keepUnchanged);

        return non_empty_string()->assert(
            implode('', $parts)
        );
    }
}
