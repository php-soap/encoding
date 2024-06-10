<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\ClassMap;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\ClassMap\ClassMap;
use Soap\Encoding\ClassMap\ClassMapCollection;

#[CoversClass(ClassMapCollection::class)]
final class ClassMapCollectionTest extends TestCase
{
    public function test_it_tests_class_maps(): void
    {
        $classMap = new ClassMapCollection(
            $item1 = new ClassMap('uri:xx', 'wsdlType', 'phpType'),
            new ClassMap('uri:xx', 'double', 'double'),
            $item2 = new ClassMap('uri:xx', 'double', 'double'),
        );

        static::assertCount(2, $classMap);
        static::assertSame([
            '{uri:xx:wsdltype}' => $item1,
            '{uri:xx:double}' => $item2,
        ], iterator_to_array($classMap));
    }

    public function test_it_can_add_types(): void
    {
        $classMap = new ClassMapCollection();
        $classMap->set($item1 = new ClassMap('uri:xx', 'wsdlType', 'phpType'));
        $classMap->set($item2 = new ClassMap('uri:xx', 'wsdlType', 'phpType'));

        static::assertSame([
            '{uri:xx:wsdltype}' => $item2,
        ], iterator_to_array($classMap));
    }
}
