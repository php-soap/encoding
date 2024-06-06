<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\PhpCompatibility;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Soap\Encoding\Decoder;
use Soap\Encoding\Driver;
use Soap\Encoding\Encoder;
use Soap\Encoding\Exception\EncodingException;
use Soap\Encoding\Exception\RestrictionException;

#[CoversClass(Driver::class)]
#[CoversClass(Encoder::class)]
#[CoversClass(Decoder::class)]
final class Schema068Test extends AbstractCompatibilityTests
{
    protected string $schema = <<<EOXML
    <complexType name="testType">
        <attribute name="str" type="string"/>
        <attribute name="int" type="int" fixed="5"/>
    </complexType>
    EOXML;
    protected string $type = 'type="tns:testType"';

    protected function calculateParam(): mixed
    {
        return (object)[
            'str' => 'str',
            'int' => 3,
        ];
    }

    #[Test] public function it_is_compatible_with_phps_encoding()
    {
        try {
            parent::it_is_compatible_with_phps_encoding();
        } catch (EncodingException $e) {
            static::assertSame('Failed encoding type stdClass as {http://test-uri/:testtype}. Failed at path "testParam.@int".', $e->getMessage());

            $previous = $e->getPrevious();
            static::assertInstanceOf(EncodingException::class, $previous);
            static::assertSame('Failed encoding type int as {http://www.w3.org/2001/XMLSchema:int}. Failed at path "@int".', $previous->getMessage());

            $previous = $previous->getPrevious();
            static::assertInstanceOf(RestrictionException::class, $previous);
            static::assertSame('Provided attribute value should be fixed to 5. Got 3', $previous->getMessage());
            return;
        }

        static::fail('Expected exception not thrown');
    }

    protected function expectXml(): string
    {
        return '<error />';
    }
}
