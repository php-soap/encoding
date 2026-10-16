<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder\Feature;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Encoder\ElementEncoder;
use Soap\Encoding\Encoder\ErrorHandlingEncoder;
use Soap\Encoding\Encoder\NullValueHandling;
use Soap\Encoding\Encoder\OptionalElementEncoder;
use Soap\Encoding\Encoder\SimpleType\StringTypeEncoder;
use Soap\Encoding\Test\Unit\ContextCreatorTrait;
use Soap\Engine\Metadata\Model\TypeMeta;
use Soap\Engine\Metadata\Model\XsdType;

#[CoversClass(OptionalElementEncoder::class), CoversClass(ErrorHandlingEncoder::class)]
final class NullValueAwareTest extends TestCase
{
    use ContextCreatorTrait;

    /**
     * @return iterable<string, array{bool, bool, int, NullValueHandling}>
     */
    public static function provideMeta(): iterable
    {
        yield 'required' => [false, false, 1, NullValueHandling::Write];
        yield 'optional, not nillable' => [true, false, 0, NullValueHandling::Skip];
        yield 'optional, nillable' => [true, true, 0, NullValueHandling::SkipWhenAbsent];
        yield 'required, nillable' => [true, true, 1, NullValueHandling::Write];
    }

    #[Test]
    #[DataProvider('provideMeta')]
    public function optional_element_encoder_decides_from_the_meta(bool $nullable, bool $nil, int $minOccurs, NullValueHandling $expected): void
    {
        $encoder = new OptionalElementEncoder(new ElementEncoder(new StringTypeEncoder()));
        $context = self::createContext(self::type($nullable, $nil, $minOccurs));

        static::assertSame($expected, $encoder->nullValueHandling($context));
        if ($expected === NullValueHandling::Skip) {
            static::assertSame('', $encoder->iso($context)->to(null));
        }
    }

    #[Test]
    #[DataProvider('provideMeta')]
    public function error_handling_encoder_forwards_the_answer(bool $nullable, bool $nil, int $minOccurs, NullValueHandling $expected): void
    {
        $encoder = new ErrorHandlingEncoder(new OptionalElementEncoder(new ElementEncoder(new StringTypeEncoder())));

        static::assertSame($expected, $encoder->nullValueHandling(self::createContext(self::type($nullable, $nil, $minOccurs))));
    }

    #[Test]
    public function error_handling_encoder_writes_for_an_encoder_that_does_not_tell(): void
    {
        $encoder = new ErrorHandlingEncoder(new ElementEncoder(new StringTypeEncoder()));

        static::assertSame(NullValueHandling::Write, $encoder->nullValueHandling(self::createContext(self::type(true, false, 0))));
    }

    private static function type(bool $nullable, bool $nil, int $minOccurs): XsdType
    {
        return XsdType::guess('string')
            ->withXmlTargetNodeName('hello')
            ->withMeta(
                static fn (TypeMeta $meta): TypeMeta => $meta
                    ->withIsNullable($nullable)
                    ->withIsNil($nil)
                    ->withMinOccurs($minOccurs)
                    ->withIsSimple(true)
                    ->withIsElement(true)
            );
    }
}
