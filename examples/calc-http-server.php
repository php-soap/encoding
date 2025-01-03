<?php declare(strict_types=1);

require_once \dirname(__DIR__) . '/vendor/autoload.php';

use Soap\Encoding\Encoder\Method\MethodContext;
use Soap\Encoding\Encoder\Method\RequestEncoder;
use Soap\Encoding\Encoder\Method\ResponseEncoder;
use Soap\Encoding\EncoderRegistry;
use Soap\Wsdl\Loader\StreamWrapperLoader;
use Soap\WsdlReader\Locator\ServiceSelectionCriteria;
use Soap\WsdlReader\Metadata\Wsdl1MetadataProvider;
use Soap\WsdlReader\Wsdl1Reader;

$wsdlLocation = __DIR__ . '/calc.wsdl';
$wsdl = (new Wsdl1Reader(new StreamWrapperLoader()))($wsdlLocation);
$registry ??= EncoderRegistry::default()
    ->addClassMap('http://tempuri.org/', 'Add', Add::class)
    ->addClassMap('http://tempuri.org/', 'AddResponse', AddResponse::class);
$metadataProvider = new Wsdl1MetadataProvider($wsdl, ServiceSelectionCriteria::defaults());
$metadata = $metadataProvider->getMetadata();

// The soap action can be detected from a PSR-7 request headers by using:
// https://github.com/php-soap/psr18-transport/blob/main/src/HttpBinding/SoapActionDetector.php
$soapAction = 'http://tempuri.org/Add';

$methodContext = new MethodContext(
    $method = $metadata->getMethods()->fetchBySoapAction($soapAction),
    $metadata,
    $registry,
    $wsdl->namespaces,
);

$request = <<<EOXML
    <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
        <soap:Body>
            <Add xmlns="http://tempuri.org/">
                <a>1</a>
                <b>2</b>
            </Add>
        </soap:Body>
    </soap:Envelope>
EOXML;

$requestEncoder = new RequestEncoder();
$requestIso = $requestEncoder->iso($methodContext);
$arguments = $requestIso->from($request);

var_dump($arguments);

final class Add
{
    public int $a;
    public int $b;
}
final class AddResponse
{
    public function __construct(
        public int $AddResult,
    ) {
    }
}

$myCalculator = new class() {
    public function Add(Add $add): AddResponse
    {
        return new AddResponse($add->a + $add->b);
    }
};


$result = $myCalculator->{$method->getName()}(...$arguments);

var_dump($result);


$responseEncoder = new ResponseEncoder();
$responseIso = $responseEncoder->iso($methodContext);
$response = $responseIso->to([$result]);

var_dump($response);
