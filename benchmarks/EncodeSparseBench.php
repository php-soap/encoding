<?php

declare(strict_types=1);

namespace Soap\Encoding\Benchmarks;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use Soap\Encoding\Driver;

/**
 * Encode benchmarks on the itemType of EncodeBench with every element minOccurs="0" nillable="true".
 *
 * Read down to compare, per scale:
 *   - all present: no value is null
 *   - all null: every element is an explicit null and written as xsi:nil
 *   - sparse: only id and address.city are set, the other properties are absent and left out
 */
#[BeforeMethods('setUp')]
#[Warmup(2)]
#[Iterations(5)]
class EncodeSparseBench
{
    use ComplexTypeBenchTrait;

    private Driver $optionalDriver;
    /** @var list<object> */
    private array $allPresent;
    /** @var list<object> */
    private array $allNull;
    /** @var list<object> */
    private array $sparse;

    public function setUp(): void
    {
        $this->optionalDriver = $this->buildPhpSoapDriver('literal', ' minOccurs="0" nillable="true"');

        $this->allPresent = [];
        $this->allNull = [];
        $this->sparse = [];
        for ($i = 0; $i < 500; $i++) {
            $this->allPresent[] = (object) [
                'id' => $i,
                'name' => 'Widget ' . $i,
                'description' => 'Description for widget ' . $i,
                'price' => 9.99 + $i,
                'quantity' => $i * 10,
                'active' => $i % 2 === 0,
                'sku' => 'WDG-' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'category' => 'Category ' . ($i % 10),
                'address' => (object) [
                    'street' => $i . ' Elm St',
                    'city' => 'City ' . ($i % 50),
                    'zip' => str_pad((string) ($i % 99999), 5, '0', STR_PAD_LEFT),
                ],
            ];
            $this->allNull[] = (object) [
                'id' => null,
                'name' => null,
                'description' => null,
                'price' => null,
                'quantity' => null,
                'active' => null,
                'sku' => null,
                'category' => null,
                'address' => null,
            ];
            $this->sparse[] = (object) [
                'id' => $i,
                'address' => (object) ['city' => 'City ' . ($i % 50)],
            ];
        }
    }

    #[Revs(100)]
    #[Groups(['literal', 'x1', 'sparse'])]
    public function benchOptionalAllPresent_x1(): void
    {
        $this->optionalDriver->encode('test', [$this->allPresent[0]]);
    }

    #[Groups(['literal', 'x500', 'sparse'])]
    public function benchOptionalAllPresent_x500(): void
    {
        foreach ($this->allPresent as $item) {
            $this->optionalDriver->encode('test', [$item]);
        }
    }

    #[Revs(100)]
    #[Groups(['literal', 'x1', 'sparse'])]
    public function benchOptionalAllNull_x1(): void
    {
        $this->optionalDriver->encode('test', [$this->allNull[0]]);
    }

    #[Groups(['literal', 'x500', 'sparse'])]
    public function benchOptionalAllNull_x500(): void
    {
        foreach ($this->allNull as $item) {
            $this->optionalDriver->encode('test', [$item]);
        }
    }

    #[Revs(100)]
    #[Groups(['literal', 'x1', 'sparse'])]
    public function benchOptionalSparse_x1(): void
    {
        $this->optionalDriver->encode('test', [$this->sparse[0]]);
    }

    #[Groups(['literal', 'x500', 'sparse'])]
    public function benchOptionalSparse_x500(): void
    {
        foreach ($this->sparse as $item) {
            $this->optionalDriver->encode('test', [$item]);
        }
    }
}
