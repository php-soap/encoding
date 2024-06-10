<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\ClassMap;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\ClassMap\ClassMap;

#[CoversClass(ClassMap::class)]
final class ClassMapTest extends TestCase
{
    public function test_it_tests_class_maps(): void
    {
        $classMap = new ClassMap('uri://xx', 'wsdlType', 'phpType');

        static::assertSame('uri://xx', $classMap->getXmlNamespace());
        static::assertSame('wsdlType', $classMap->getXmlType());
        static::assertSame('phpType', $classMap->getPhpClassName());
    }
}
