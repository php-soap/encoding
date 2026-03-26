<?php

declare(strict_types=1);

namespace Soap\Encoding\Benchmarks;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

/**
 * Decode benchmarks: all implementations, modes, and scales in one place.
 *
 * ext-soap note: __soapCall always encodes + decodes. These benchmarks
 * include encode overhead. Subtract EncodeBench times to estimate decode-only.
 */
#[BeforeMethods('setUp')]
#[Warmup(2)]
#[Iterations(5)]
class DecodeBench
{
    use ComplexTypeBenchTrait;

    // -- php-soap/encoding: literal --

    #[Revs(100)]
    #[Groups(['literal', 'php-soap', 'x1'])]
    public function benchLiteral_x1(): void
    {
        $this->literalDriver->decode('test', $this->literalResponse);
    }

    #[Groups(['literal', 'php-soap', 'x500'])]
    public function benchLiteral_x500(): void
    {
        $response = $this->literalResponse;
        foreach ($this->items as $item) {
            $this->literalDriver->decode('test', $response);
        }
    }

    // -- php-soap/encoding: encoded --

    #[Revs(100)]
    #[Groups(['encoded', 'php-soap', 'x1'])]
    public function benchEncoded_x1(): void
    {
        $this->encodedDriver->decode('test', $this->encodedResponse);
    }

    #[Groups(['encoded', 'php-soap', 'x500'])]
    public function benchEncoded_x500(): void
    {
        $response = $this->encodedResponse;
        foreach ($this->items as $item) {
            $this->encodedDriver->decode('test', $response);
        }
    }

    // -- ext-soap: literal (includes encode overhead) --

    #[Revs(100)]
    #[Groups(['literal', 'ext-soap', 'x1'])]
    public function benchExtSoapLiteral_x1(): void
    {
        $this->extSoapLiteral->mockResponse = $this->fullResponse;
        $this->extSoapLiteral->__soapCall('test', ['testParam' => $this->singleItem]);
    }

    #[Groups(['literal', 'ext-soap', 'x500'])]
    public function benchExtSoapLiteral_x500(): void
    {
        $this->extSoapLiteral->mockResponse = $this->fullResponse;
        foreach ($this->items as $item) {
            $this->extSoapLiteral->__soapCall('test', ['testParam' => $item]);
        }
    }

    // -- ext-soap: encoded (includes encode overhead) --

    #[Revs(100)]
    #[Groups(['encoded', 'ext-soap', 'x1'])]
    public function benchExtSoapEncoded_x1(): void
    {
        $this->extSoapEncoded->mockResponse = $this->fullResponse;
        $this->extSoapEncoded->__soapCall('test', ['testParam' => $this->singleItem]);
    }

    #[Groups(['encoded', 'ext-soap', 'x500'])]
    public function benchExtSoapEncoded_x500(): void
    {
        $this->extSoapEncoded->mockResponse = $this->fullResponse;
        foreach ($this->items as $item) {
            $this->extSoapEncoded->__soapCall('test', ['testParam' => $item]);
        }
    }
}
