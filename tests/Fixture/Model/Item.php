<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Fixture\Model;

final class Item
{
    public int $id;
    public string $name;
    public ?float $price;
    public ?bool $active;
    public ?Address $address;
    /** @var list<string>|null */
    public ?array $tags;
    public ?string $code;
    public ?int $rank;
}
