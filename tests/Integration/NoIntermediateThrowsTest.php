<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Integration;

use Closure;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Encoder\SimpleType\ScalarTypeEncoder;
use Soap\Encoding\Encoder\SimpleType\SimpleListEncoder;
use Soap\Encoding\Encoder\SoapEnc\ApacheMapEncoder;
use Soap\Encoding\Encoder\SoapEnc\SoapObjectEncoder;
use Soap\Encoding\Test\Support\Throws\AssertsNoThrows;
use Soap\Encoding\Test\Unit\ContextCreatorTrait;
use Soap\Engine\Metadata\Model\TypeMeta;
use Soap\Engine\Metadata\Model\XsdType;
use function var_export;

#[CoversNothing]
#[Group('no-throws')]
final class NoIntermediateThrowsTest extends TestCase
{
    use AssertsNoThrows;
    use ContextCreatorTrait;

    /**
     * @param Closure(): mixed $operation
     */
    #[DataProvider('provideScenarios')]
    public function test_it_does_not_throw_internally(Closure $operation, mixed $expected): void
    {
        $operation();

        static::assertNoThrows($operation);
    }

    /**
     * @param Closure(): mixed $decode
     */
    #[DataProvider('provideUntypedDecodes')]
    public function test_it_decodes_untyped_values_without_throwing(Closure $decode, mixed $expected): void
    {
        static::assertEquals($expected, $decode());

        static::assertNoThrows($decode);
    }

    /**
     * @return iterable<string, array{decode: Closure(): mixed, expected: mixed}>
     */
    public static function provideUntypedDecodes(): iterable
    {
        $scalar = ScalarTypeEncoder::default()->iso(self::createContext(XsdType::any()));
        foreach (['1.5' => 1.5, '1e3' => 1000.0, 'abc' => 'abc', '' => '', 'true' => true, 'false' => false, '12' => 12] as $value => $expected) {
            $value = (string) $value;
            yield 'scalar ' . var_export($value, true) => [
                'decode' => static fn (): mixed => $scalar->from($value),
                'expected' => $expected,
            ];
        }

        $list = (new SimpleListEncoder(ScalarTypeEncoder::default()))->iso(self::createContext(
            XsdType::any()->withMeta(static fn (TypeMeta $meta): TypeMeta => $meta->withIsList(true)->withIsSimple(true))
        ));
        yield 'simple list' => [
            'decode' => static fn (): mixed => $list->from('1 1.5 abc true'),
            'expected' => [1, 1.5, 'abc', true],
        ];

        $map = (new ApacheMapEncoder())->iso(self::createContext(XsdType::guess('Map')));
        yield 'apache map values' => [
            'decode' => static fn (): mixed => $map->from(
                '<map><item><key>a</key><value>abc</value></item><item><key>b</key><value>1.5</value></item></map>'
            ),
            'expected' => ['a' => 'abc', 'b' => 1.5],
        ];

        $object = (new SoapObjectEncoder())->iso(self::createContext(XsdType::guess('Struct')));
        yield 'soap-enc struct values' => [
            'decode' => static fn (): mixed => $object->from('<obj><a>abc</a><b>1.5</b><c>false</c></obj>'),
            'expected' => (object) ['a' => 'abc', 'b' => 1.5, 'c' => false],
        ];
    }

    /**
     * @return iterable<string, array{operation: Closure(): mixed, expected: mixed}>
     */
    public static function provideScenarios(): iterable
    {
        return IntermediateThrowScenariosTest::provideScenarios();
    }
}
