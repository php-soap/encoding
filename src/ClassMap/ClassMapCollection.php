<?php declare(strict_types=1);

namespace Soap\Encoding\ClassMap;

use ArrayIterator;
use IteratorAggregate;
use Soap\Encoding\Formatter\QNameFormatter;

/**
 * @implements IteratorAggregate<string, ClassMap>
 */
final class ClassMapCollection implements IteratorAggregate
{
    /**
     * @var array<string, ClassMap>
     */
    private array $classMaps = [];

    public function __construct(ClassMap ... $classMaps)
    {
        foreach ($classMaps as $classMap) {
            $this->set($classMap);
        }
    }

    public function set(ClassMap $classMap): self
    {
        $qname = (new QNameFormatter())($classMap->getXmlNamespace(), $classMap->getXmlType());
        $this->classMaps[$qname] = $classMap;

        return $this;
    }

    /**
     * @return ArrayIterator<string, ClassMap>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->classMaps);
    }
}
