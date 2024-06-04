<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\PhpCompatibility;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Soap\Encoding\Decoder;
use Soap\Encoding\Driver;
use Soap\Encoding\Encoder;

#[CoversClass(Driver::class)]
#[CoversClass(Encoder::class)]
#[CoversClass(Decoder::class)]
class Schema068Test extends AbstractCompatibilityTests
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
        $this->markTestIncomplete('No fixed value is being validates (yet)');

        $this->expectException(\Exception::class);
        parent::it_is_compatible_with_phps_encoding();
    }

    protected function expectXml(): string
    {
        return '<error />';
    }
}
