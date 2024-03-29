<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder;

use PHPUnit\Framework\Attributes\CoversClass;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\ObjectEncoder;
use Soap\Encoding\Test\Fixture\Model\Hat;
use Soap\Encoding\Test\Fixture\Model\User;
use Soap\Engine\Metadata\Collection\PropertyCollection;
use Soap\Engine\Metadata\Collection\TypeCollection;
use Soap\Engine\Metadata\Model\Property;
use Soap\Engine\Metadata\Model\Type;
use Soap\Engine\Metadata\Model\TypeMeta;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\Xml\Xmlns;
use function Psl\Fun\tap;

#[CoversClass(ObjectEncoder::class)]
class ObjectEncoderTest extends AbstractEncoderTests
{
    public static function provideIsomorphicCases(): iterable
    {
        $baseConfig = [
            'encoder' => $encoder = new ObjectEncoder(\stdClass::class),
            'context' => $context = self::createContext(
                $xsdType = XsdType::create('user')
                    ->withXmlNamespace("https://test")
                    ->withXmlNamespaceName('test')
                    ->withXmlTargetNodeName('user'),
            ),
        ];

        $withClassMap = tap(
            static fn (Context $context) => $context->registry
                ->addClassMap('https://test', 'user', User::class)
                ->addClassMap('https://test', 'hat', Hat::class)
        );

        yield 'simple-objects' => [
            ...$baseConfig,
            'context' => self::createContext($xsdType, self::buildTypes()),
            'xml' => '<user><active>true</active><hat><color>green</color></hat></user>',
            'data' => (object)[
                'active' => true,
                'hat' => (object)[
                    'color' => 'green',
                ],
            ],
        ];
        yield 'with-attribute' => [
            ...$baseConfig,
            'context' => self::createContext($xsdType, self::buildTypes(activeAsAttribute: true)),
            'xml' => '<user active="true"><hat><color>green</color></hat></user>',
            'data' => (object)[
                'active' => true,
                'hat' => (object)[
                    'color' => 'green',
                ],
            ],
        ];
        yield 'with-namespace' => [
            ...$baseConfig,
            'context' => self::createContext(
                $xsdType->withXmlTargetNamespace('http://target.ns'),
                self::buildTypes()
            ),
            'xml' => '<user xmlns="http://target.ns"><active>true</active><hat><color>green</color></hat></user>',
            'data' => (object)[
                'active' => true,
                'hat' => (object)[
                    'color' => 'green',
                ],
            ],
        ];
        yield 'with-namespace-prefix' => [
            ...$baseConfig,
            'context' => self::createContext(
                $xsdType
                    ->withXmlTargetNamespace('http://target.ns')
                    ->withXmlTargetNamespaceName('tg'),
                self::buildTypes()
            ),
            'xml' => '<tg:user xmlns:tg="http://target.ns"><active>true</active><hat><color>green</color></hat></tg:user>',
            'data' => (object)[
                'active' => true,
                'hat' => (object)[
                    'color' => 'green',
                ],
            ],
        ];

        yield 'class-objects' => [
            'encoder' => new ObjectEncoder(User::class),
            'context' => $withClassMap(self::createContext($xsdType, self::buildTypes())),
            'xml' => '<user><active>true</active><hat><color>green</color></hat></user>',
            'data' => new User(active: true, hat: new Hat('green')),
        ];
        yield 'class-objects-with-attribute' => [
            'encoder' => new ObjectEncoder(User::class),
            'context' => $withClassMap(self::createContext($xsdType, self::buildTypes(activeAsAttribute: true))),
            'xml' => '<user active="true"><hat><color>green</color></hat></user>',
            'data' => new User(active: true, hat: new Hat('green')),
        ];
    }


    public static function buildTypes(
        bool $activeAsAttribute = false,
    ): TypeCollection {
        return new TypeCollection(
            new Type(
                XsdType::create('user')
                    ->withXmlNamespace("https://test")
                    ->withXmlNamespaceName('test')
                    ->withXmlTargetNodeName('user'),
                new PropertyCollection(
                    new Property(
                        'active',
                        XsdType::create('active')
                            ->withBaseType('boolean')
                            ->withXmlTargetNodeName('active')
                            ->withXmlNamespace(Xmlns::xsd()->value())
                            ->withXmlNamespaceName('xsd')
                            ->withMeta(fn (TypeMeta $meta): TypeMeta => $activeAsAttribute ? $meta->withIsAttribute(true) : $meta)
                    ),
                    new Property(
                        'hat',
                        XsdType::create('hat')
                            ->withBaseType('hat')
                            ->withXmlTargetNodeName('hat')
                            ->withXmlNamespace('https://test')
                            ->withXmlNamespaceName('test')
                    )
                )
            ),
            new Type(
                XsdType::create('hat')
                    ->withXmlNamespace("https://test")
                    ->withXmlNamespaceName('test')
                    ->withXmlTargetNodeName('hat'),
                new PropertyCollection(
                    new Property(
                        'color',
                        XsdType::create('color')
                            ->withBaseType('string')
                            ->withXmlTargetNodeName('color')
                            ->withXmlNamespace(Xmlns::xsd()->value())
                            ->withXmlNamespaceName('xsd')
                    ),
                )
            )
        );
    }
}
