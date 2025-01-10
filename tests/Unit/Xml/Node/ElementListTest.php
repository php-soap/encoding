<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Xml\Node;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Node\ElementList;

#[CoversClass(ElementList::class)]
final class ElementListTest extends TestCase
{
    public function test_it_can_be_created_from_string(): void
    {
        $list = ElementList::fromString('<list><hello>world</hello><hello>sun</hello></list>');

        static::assertCount(2, $list->elements());
        static::assertSame('<hello>world</hello>', $list->elements()[0]->value());
        static::assertSame('<hello>sun</hello>', $list->elements()[1]->value());
    }

    public function test_it_can_append_elements(): void
    {
        $list = ElementList::fromString('<list><hello>world</hello></list>');
        $result = $list->append(Element::fromString('<hello>sun</hello>'));

        static::assertSame($list, $result);
        static::assertCount(2, $result->elements());
        static::assertSame('<hello>world</hello>', $result->elements()[0]->value());
        static::assertSame('<hello>sun</hello>', $result->elements()[1]->value());
    }

    public function test_it_can_be_constructed(): void
    {
        $element = Element::fromString($xml = '<hello>world</hello>');
        $list = new ElementList($element);

        static::assertCount(1, $list->elements());
        static::assertSame($xml, $list->elements()[0]->value());
    }

    public function test_it_can_load_nested_list(): void
    {
        $list = ElementList::fromLookupArray([
            'hello' => $hello = Element::fromString('<hello>world</hello>'),
            'world' => '',
            '_' => '',
            'attr' => 'foo',
            'list' => new ElementList(
                $list1 = Element::fromString('<list>1</list>'),
                $list2 = Element::fromString('<list>2</list>'),
            )
        ]);

        static::assertSame([$hello, $list1, $list2], $list->elements());
    }

    public function test_it_can_be_countded(): void
    {
        $list = new ElementList(Element::fromString('<hello>world</hello>'));

        static::assertCount(1, $list->elements());
        static::assertCount(1, $list);
    }
}
