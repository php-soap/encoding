<?php

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\EncoderRegistry;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\Xml\Xmlns;

require_once __DIR__.'/vendor/autoload.php';

$registry = EncoderRegistry::default();
$encoder = $registry->findByXsdType(Xmlns::xsd()->value(), 'boolean');


$context = new Context(
    XsdType::create('root')
        ->withXmlNamespace("https://test")
        ->withXmlNamespaceName('test')
);

var_dump($encoder->map(fn (XmlEncoder $encoder) => $encoder->iso($context)->to(false)));
