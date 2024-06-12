<?php
declare(strict_types=1);

namespace Soap\Encoding\Restriction;

use Psl\Regex;
use Psl\Str;
use Soap\Encoding\Encoder\Context;
use function Psl\Type\string;

final class WhitespaceRestriction
{
    public const PRESERVE = 'preserve';
    public const REPLACE = 'replace';
    public const COLLAPSE = 'collapse';

    public static function parseForContext(Context $context, string $value): string
    {
        $type = $context->type;
        $meta = $type->getMeta();
        $restrictions = $meta->restriction()->unwrapOr([]);
        $whitespace = mb_strtolower(string()->assert($restrictions['whiteSpace'][0]['value'] ?? self::PRESERVE));

        return match ($whitespace) {
            self::REPLACE => self::replace($value),
            self::COLLAPSE => self::collapse($value),
            default => $value,
        };
    }

    public static function preserve(string $value): string
    {
        return $value;
    }

    /**
     *  Replaces line feeds, tabs, spaces, and carriage returns) with spaces:
     */
    public static function replace(string $value): string
    {
        return Regex\replace($value, '/[\r\n\t\f\v]+/', ' ');
    }

    /**
     * Replaces line feeds, tabs, spaces, carriage returns are replaced with spaces,
     * leading and trailing spaces are removed,
     * and multiple spaces are reduced to a single space
     */
    public static function collapse(string $value): string
    {
        return Str\trim(
            Regex\replace($value, '/\s+/', ' ')
        );
    }
}
