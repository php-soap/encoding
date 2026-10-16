<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\Feature;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Encoder\ElementEncoder;
use Soap\Encoding\Encoder\ErrorHandlingEncoder;
use Soap\Encoding\Encoder\OptionalElementEncoder;
use Soap\Encoding\Encoder\SimpleType\StringTypeEncoder;
use Soap\Encoding\Test\Unit\ContextCreatorTrait;
use Soap\Engine\Metadata\Model\TypeMeta;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(OptionalElementEncoder::class), CoversClass(ErrorHandlingEncoder::class)]
final class WritesNothingForNullTest extends TestCase
{
    use ContextCreatorTrait;

    /**
     * @return iterable<string, array{bool, bool, bool}>
     */
    public static function provideMeta(): iterable
    {
        yield 'optional, not nillable' => [true, false, true];
        yield 'optional, nillable' => [true, true, false];
        yield 'required' => [false, false, false];
    }

    #[Test]
    #[DataProvider('provideMeta')]
    public function optional_element_encoder_writes_nothing_for_null_unless_nillable(bool $nullable, bool $nil, bool $expected): void
    {
        $encoder = new OptionalElementEncoder(new ElementEncoder(new StringTypeEncoder()));
        $context = self::createContext(self::type($nullable, $nil));

        static::assertSame($expected, $encoder->writesNothingForNull($context));
        if ($expected) {
            static::assertSame('', $encoder->iso($context)->to(null));
        }
    }

    #[Test]
    #[DataProvider('provideMeta')]
    public function error_handling_encoder_forwards_the_answer(bool $nullable, bool $nil, bool $expected): void
    {
        $encoder = new ErrorHandlingEncoder(new OptionalElementEncoder(new ElementEncoder(new StringTypeEncoder())));

        static::assertSame($expected, $encoder->writesNothingForNull(self::createContext(self::type($nullable, $nil))));
    }

    #[Test]
    public function error_handling_encoder_answers_no_for_an_encoder_that_does_not_tell(): void
    {
        $encoder = new ErrorHandlingEncoder(new ElementEncoder(new StringTypeEncoder()));

        static::assertFalse($encoder->writesNothingForNull(self::createContext(self::type(true, false))));
    }

    private static function type(bool $nullable, bool $nil): XsdType
    {
        return XsdType::guess('string')
            ->withXmlTargetNodeName('hello')
            ->withMeta(
                static fn (TypeMeta $meta): TypeMeta => $meta
                    ->withIsNullable($nullable)
                    ->withIsNil($nil)
                    ->withIsSimple(true)
                    ->withIsElement(true)
            );
    }
}
