<?php

use Soap\Encoding\Encoder\XmlEncoder;
use Soap\Encoding\EncoderRegistry;
use Soap\Xml\Xmlns;

require_once __DIR__.'/vendor/autoload.php';

$registry = EncoderRegistry::default();
$encoder = $registry->findByXsdType(Xmlns::xsd()->value(), 'int');

var_dump($encoder->map(fn (XmlEncoder $encoder) => $encoder->iso()->from('32')));
