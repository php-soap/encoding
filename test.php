<?php

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\ObjectEncoder;
use Soap\Encoding\EncoderRegistry;
use Soap\Engine\Metadata\Collection\MethodCollection;
use Soap\Engine\Metadata\Collection\PropertyCollection;
use Soap\Engine\Metadata\Collection\TypeCollection;
use Soap\Engine\Metadata\InMemoryMetadata;
use Soap\Engine\Metadata\Model\Property;
use Soap\Engine\Metadata\Model\Type;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\Xml\Xmlns;

require_once __DIR__.'/vendor/autoload.php';

class User {
    public function __construct(
        public bool $active,
        public Hat $hat
    ) {
    }
}

class Hat {
    public function __construct(
        public string $color,
    ) {
    }
}

$registry = EncoderRegistry::default()
    ->addClassMap('https://test', 'user', \User::class)
    ->addClassMap('https://test', 'hat', \Hat::class);

$context = new Context(
    XsdType::create('user')
        ->withXmlNamespace("https://test")
        ->withXmlNamespaceName('test'),
    new InMemoryMetadata(
        new TypeCollection(
            new Type(
                XsdType::create('user')
                    ->withXmlNamespace("https://test")
                    ->withXmlNamespaceName('test'),
                new PropertyCollection(
                    new Property(
                        'active',
                        XsdType::create('active')
                            ->withBaseType('boolean') // TODO : this is not correct ! figure out how to find te relatable element name
                            ->withXmlNamespace(Xmlns::xsd()->value())
                            ->withXmlNamespaceName('xsd')
                    ),
                    new Property(
                        'hat',
                        XsdType::create('hat')
                            ->withBaseType('hat') // TODO : this is not correct ! figure out how to find te relatable element name
                            ->withXmlNamespace('https://test')
                            ->withXmlNamespaceName('test')
                    )
                )
            ),
            new Type(
                XsdType::create('hat')
                    ->withXmlNamespace("https://test")
                    ->withXmlNamespaceName('test'),
                new PropertyCollection(
                    new Property(
                        'color',
                        XsdType::create('color')
                            ->withBaseType('string') // TODO : this is not correct ! figure out how to find te relatable element name
                            ->withXmlNamespace(Xmlns::xsd()->value())
                            ->withXmlNamespaceName('xsd')
                    ),
                )
            )
        ),
        new MethodCollection(),
    ),
    $registry
);


$user = new User(active: true, hat: new Hat('green'));
$encoder = $registry->findByXsdType($context->type);

var_dump(
    $xml = $encoder->iso($context)->to($user),
    $encoder->iso($context)->from($xml),
);


