<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit\Encoder;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\ErrorHandlingEncoder;
use Soap\Encoding\Encoder\ObjectEncoder;
use Soap\Encoding\Encoder\SimpleType\StringTypeEncoder;
use Soap\Encoding\Exception\EncodingException;
use Soap\Engine\Metadata\Collection\PropertyCollection;
use Soap\Engine\Metadata\Collection\TypeCollection;
use Soap\Engine\Metadata\Model\Property;
use Soap\Engine\Metadata\Model\Type;
use Soap\Engine\Metadata\Model\TypeMeta;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\Xml\Xmlns;
use stdClass;

#[CoversClass(ErrorHandlingEncoder::class)]
#[CoversClass(EncodingException::class)]
final class ErrorHandlingEncoderTest extends AbstractEncoderTests
{
    #[Test]
    public function it_can_fail_encoding(): void
    {
        $encoder = new ErrorHandlingEncoder(new ObjectEncoder(stdClass::class));
        $context = self::buildObjectContextForFailure();

        try {
            $encoder->iso($context)->to((object)[
                'active' => true,
                'hat' => (object)[
                    'color' => 'not-a-float',
                ],
            ]);
        } catch (EncodingException $e) {
            static::assertSame('Failed encoding type stdClass as {https://test:user}. Failed at path "user.hat.color".', $e->getMessage());
            static::assertSame(['user', 'hat', 'color'], $e->getPaths());

            $previous = $e->getPrevious();
            static::assertInstanceOf(EncodingException::class, $previous);
            static::assertSame('Failed encoding type stdClass as {https://test:hat}. Failed at path "hat.color".', $previous->getMessage());
            static::assertSame(['hat', 'color'], $previous->getPaths());

            $previous = $previous->getPrevious();
            static::assertInstanceOf(EncodingException::class, $previous);
            static::assertSame('Failed encoding type string as {http://www.w3.org/2001/XMLSchema:float}. Failed at path "color".', $previous->getMessage());
            static::assertSame(['color'], $previous->getPaths());

            return;
        }

        static::fail('Encoding should have failed: no EncodingException received.');
    }

    #[Test]
    public function it_can_fail_decoding(): void
    {
        $encoder = new ErrorHandlingEncoder(new ObjectEncoder(stdClass::class));
        $context = self::buildObjectContextForFailure();

        try {
            $res = $encoder->iso($context)->from(
                '<user><active>true</active><hat><color>not-a-float</color></hat></user>'
            );

            dd($res);
        } catch (EncodingException $e) {
            static::assertSame('Failed decoding type string as {https://test:user}. Failed at path "user.hat.color".', $e->getMessage());
            static::assertSame(['user', 'hat', 'color'], $e->getPaths());

            $previous = $e->getPrevious();
            static::assertInstanceOf(EncodingException::class, $previous);
            static::assertSame('Failed decoding type string as {https://test:hat}. Failed at path "hat.color".', $previous->getMessage());
            static::assertSame(['hat', 'color'], $previous->getPaths());

            $previous = $previous->getPrevious();
            static::assertInstanceOf(EncodingException::class, $previous);
            static::assertSame('Failed decoding type string as {http://www.w3.org/2001/XMLSchema:float}. Failed at path "color".', $previous->getMessage());
            static::assertSame(['color'], $previous->getPaths());

            return;
        }

        static::fail('Encoding should have failed: no EncodingException received.');
    }

    public static function provideIsomorphicCases(): iterable
    {
        yield 'proxies-simple-string' => [
            'encoder' => new ErrorHandlingEncoder(new StringTypeEncoder()),
            'context' => self::createContext(XsdType::guess('string')),
            'xml' => 'hello',
            'data' => 'hello',
        ];

        yield 'proxies-simple-object' => [
            'encoder' => new ErrorHandlingEncoder(new ObjectEncoder(stdClass::class)),
            'context' => self::createContext(
                ObjectEncoderTest::buildTypes()->fetchFirstByName('user')->getXsdType(),
                ObjectEncoderTest::buildTypes()
            ),
            'xml' => '<user><active>true</active><hat><color>green</color></hat></user>',
            'data' => (object)[
                'active' => true,
                'hat' => (object)[
                    'color' => 'green',
                ],
            ],
        ];
    }

    private static function buildObjectContextForFailure(): Context
    {
        $objectTypes = ObjectEncoderTest::buildTypes();
        $user = $objectTypes->fetchFirstByName('user');
        $hat = new Type(
            $objectTypes->fetchFirstByName('hat')->getXsdType(),
            new PropertyCollection(
                new Property(
                    'color',
                    XsdType::create('color')
                        ->withXmlTypeName('float')
                        ->withXmlTargetNodeName('color')
                        ->withXmlNamespace(Xmlns::xsd()->value())
                        ->withXmlNamespaceName('xsd')
                        ->withMeta(
                            static fn (TypeMeta $meta): TypeMeta => $meta
                                ->withIsSimple(true)
                                ->withIsElement(true)
                                ->withIsQualified(true)
                        )
                ),
            )
        );

        return self::createContext(
            $user->getXsdType(),
            new TypeCollection(
                $user,
                $hat,
            )
        );
    }
}
