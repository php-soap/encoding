<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Fixture\Model;

/**
 * Holds the address in a private property, so a child class instance only reaches it through the parent.
 */
abstract class PrivateAddressItem
{
    public int $id = 1;
    private ?Address $address = null;
}
