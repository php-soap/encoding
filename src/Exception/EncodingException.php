<?php
declare(strict_types=1);

namespace Soap\Encoding\Exception;

use Exception;
use Psl\Str;
use Psl\Vec;
use Soap\Encoding\Formatter\QNameFormatter;
use Soap\Engine\Metadata\Model\XsdType;
use Throwable;

final class EncodingException extends Exception implements ExceptionInterface
{
    /**
     * @var list<string>
     */
    private array $paths;

    /**
     * @param list<string> $paths
     */
    private function __construct(string $message, array $paths, Throwable $previous)
    {
        parent::__construct(
            Str\format(
                '%s%s',
                $message,
                $paths ? ' Failed at path "' . Str\join($paths, '.') . '".' : ''
            ),
            0,
            $previous
        );

        $this->paths = $paths;
    }

    public static function encodingValue(
        mixed $value,
        XsdType $expectedType,
        Throwable $previous,
        ?string $path = null
    ):self {
        $paths = $previous instanceof EncodingException ? [$path, ...$previous->getPaths()] : [$path];

        return new self(
            Str\format(
                'Failed encoding type %s as %s.',
                get_debug_type($value),
                (new QNameFormatter())($expectedType->getXmlNamespace(), $expectedType->getXmlTypeName()),
            ),
            Vec\filter_nulls($paths),
            $previous
        );
    }

    public static function decodingValue(
        mixed $value,
        XsdType $expectedType,
        Throwable $previous,
        ?string $path = null
    ): self {
        $paths = $previous instanceof EncodingException ? [$path, ...$previous->getPaths()] : [$path];

        return new self(
            Str\format(
                'Failed decoding type %s as %s.',
                get_debug_type($value),
                (new QNameFormatter())($expectedType->getXmlNamespace(), $expectedType->getXmlTypeName()),
            ),
            Vec\filter_nulls($paths),
            $previous
        );
    }

    public function getPaths(): array
    {
        return $this->paths;
    }
}
