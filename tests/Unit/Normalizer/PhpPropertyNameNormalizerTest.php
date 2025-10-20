<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Normalizer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Normalizer\PhpPropertyNameNormalizer;

#[CoversClass(PhpPropertyNameNormalizer::class)]
final class PhpPropertyNameNormalizerTest extends TestCase
{

    #[DataProvider('provideCases')]
    public function test_it_can_normalize(string $in, string $expected): void
    {
        static::assertEquals($expected, PhpPropertyNameNormalizer::normalize($in));
    }

    public static function provideCases()
    {
        yield ['prop1', 'prop1'];
        yield ['final', 'final'];
        yield ['Final', 'Final'];
        yield ['UpperCased', 'UpperCased'];
        yield ['my-./*prop_123', 'myProp_123'];
        yield ['My-./*prop_123', 'MyProp_123'];
        yield ['My-./final*prop_123', 'MyFinalProp_123'];
    }
}
