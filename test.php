<?php

use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\EncoderRegistry;
use Soap\Xml\Xmlns;

require_once __DIR__.'/vendor/autoload.php';

$registry = EncoderRegistry::default();
$encoder = $registry->findByXsdType(Xmlns::xsd()->value(), 'float');

var_dump($encoder->map(fn (XmlEncoder $encoder) => $encoder->iso()->from('<root>10.2</root>')));
