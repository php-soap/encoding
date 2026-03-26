<?php

declare(strict_types=1);

namespace Soap\Encoding\Benchmarks;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use Soap\Encoding\Driver;
use Soap\Encoding\EncoderRegistry;
use Soap\Wsdl\Loader\CallbackLoader;
use Soap\WsdlReader\Metadata\Wsdl1MetadataProvider;
use Soap\WsdlReader\Wsdl1Reader;

/**
 * Encode benchmarks: all implementations, modes, and scales in one place.
 *
 * Read down to compare:
 *   - Literal x1 vs x500 (scale)
 *   - Literal vs Encoded (mode)
 *   - php-soap vs ext-soap (implementation)
 */
#[BeforeMethods('setUp')]
#[Warmup(2)]
#[Iterations(5)]
class EncodeBench
{
    use ComplexTypeBenchTrait;

    // -- php-soap/encoding: literal --

    #[Revs(100)]
    #[Groups(['literal', 'x1'])]
    public function benchLiteral_x1(): void
    {
        $this->literalDriver->encode('test', [$this->singleItem]);
    }

    #[Groups(['literal', 'x500'])]
    public function benchLiteral_x500(): void
    {
        foreach ($this->items as $item) {
            $this->literalDriver->encode('test', [$item]);
        }
    }

    // -- php-soap/encoding: encoded --

    #[Revs(100)]
    #[Groups(['encoded', 'x1'])]
    public function benchEncoded_x1(): void
    {
        $this->encodedDriver->encode('test', [$this->singleItem]);
    }

    #[Groups(['encoded', 'x500'])]
    public function benchEncoded_x500(): void
    {
        foreach ($this->items as $item) {
            $this->encodedDriver->encode('test', [$item]);
        }
    }

    // -- ext-soap: literal --

    #[Revs(100)]
    #[Groups(['literal', 'ext-soap', 'x1'])]
    public function benchExtSoapLiteral_x1(): void
    {
        $this->extSoapLiteral->mockResponse = $this->minimalResponse;
        $this->extSoapLiteral->__soapCall('test', ['testParam' => $this->singleItem]);
    }

    #[Groups(['literal', 'ext-soap', 'x500'])]
    public function benchExtSoapLiteral_x500(): void
    {
        $this->extSoapLiteral->mockResponse = $this->minimalResponse;
        foreach ($this->items as $item) {
            $this->extSoapLiteral->__soapCall('test', ['testParam' => $item]);
        }
    }

    // -- ext-soap: encoded --

    #[Revs(100)]
    #[Groups(['encoded', 'ext-soap', 'x1'])]
    public function benchExtSoapEncoded_x1(): void
    {
        $this->extSoapEncoded->mockResponse = $this->minimalResponse;
        $this->extSoapEncoded->__soapCall('test', ['testParam' => $this->singleItem]);
    }

    #[Groups(['encoded', 'ext-soap', 'x500'])]
    public function benchExtSoapEncoded_x500(): void
    {
        $this->extSoapEncoded->mockResponse = $this->minimalResponse;
        foreach ($this->items as $item) {
            $this->extSoapEncoded->__soapCall('test', ['testParam' => $item]);
        }
    }
}
