<?php declare(strict_types=1);

use GoetasWebservices\XML\XSDReader\SchemaReader;
use Soap\Encoding\Encoder\Context;
use Soap\Encoding\EncoderRegistry;
use Soap\Engine\Metadata\Collection\MethodCollection;
use Soap\Engine\Metadata\InMemoryMetadata;
use Soap\WsdlReader\Metadata\Converter\SchemaToTypesConverter;
use Soap\WsdlReader\Metadata\Converter\Types\TypesConverterContext;
use Soap\WsdlReader\Parser\Definitions\NamespacesParser;
use VeeWee\Xml\Dom\Document;

require_once \dirname(__DIR__) . '/vendor/autoload.php';

// Load the XSD with the goetas-webservices/xsd-reader package and transform it to a metadata object:
$xsd = Document::fromXmlFile($file = __DIR__.'/calc.xsd');
$reader = new SchemaReader();
$schema = $reader->readNode($xsd->locateDocumentElement(), $file);
$namespaces = NamespacesParser::tryParse($xsd);
$types = (new SchemaToTypesConverter())(
    $schema,
    TypesConverterContext::default($namespaces)
);
$metadata = new InMemoryMetadata($types, new MethodCollection());

// Create an encoder for the Add type context:
$registry = EncoderRegistry::default();
$encoder = $registry->detectEncoderForContext(
    $context = new Context(
        $types->fetchFirstByName('Add')->getXsdType(),
        $metadata,
        $registry,
        $namespaces,
    )
);

// Decode + Encode the Add type:
var_dump($data = $encoder->iso($context)->from(
    <<<EOXML
    <Add xmlns="http://tempuri.org/">
        <a>1</a>
        <b>2</b>
    </Add>
EOXML
));

var_dump($encoder->iso($context)->to($data));
