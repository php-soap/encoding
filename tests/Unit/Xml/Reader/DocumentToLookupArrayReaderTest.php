<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Xml\Reader;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Soap\Encoding\Xml\Node\Element;
use Soap\Encoding\Xml\Node\ElementList;
use Soap\Encoding\Xml\Reader\DocumentToLookupArrayReader;

#[CoversClass(DocumentToLookupArrayReader::class)]
final class DocumentToLookupArrayReaderTest extends TestCase
{
    #[Test]
    public function it_keeps_children_when_an_attribute_has_the_same_name(): void
    {
        $lookup = (new DocumentToLookupArrayReader())(Element::fromString(
            '<item xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="Record" type="attribute"><type>first</type><type>second</type></item>'
        ));

        static::assertSame(['type'], array_keys($lookup));
        static::assertInstanceOf(ElementList::class, $lookup['type']);
        static::assertCount(2, $lookup['type']);
    }

    #[Test]
    public function it_leaves_out_protocol_attributes(): void
    {
        $lookup = (new DocumentToLookupArrayReader())(Element::fromString(
            '<item xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:enc="http://schemas.xmlsoap.org/soap/encoding/"'
            . ' xsi:type="Record" xsi:nil="false" enc:arrayType="x[0]" label="text">content</item>'
        ));

        static::assertSame(['_' => 'content', 'label' => 'text'], $lookup);
    }
}
