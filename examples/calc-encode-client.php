<?php declare(strict_types=1);

require_once \dirname(__DIR__) . '/vendor/autoload.php';

use Soap\Encoding\Driver;
use Soap\Encoding\EncoderRegistry;
use Soap\Engine\HttpBinding\SoapResponse;
use Soap\Wsdl\Loader\StreamWrapperLoader;
use Soap\WsdlReader\Locator\ServiceSelectionCriteria;
use Soap\WsdlReader\Model\Definitions\SoapVersion;
use Soap\WsdlReader\Wsdl1Reader;

$wsdlLocation = 'https://ecs.syr.edu/faculty/fawcett/Handouts/cse775/code/calcWebService/Calc.asmx?wsdl';

$loader = new StreamWrapperLoader();
$wsdl = (new Wsdl1Reader($loader))($wsdlLocation);

$driver = Driver::createFromWsdl1(
    $wsdl,
    ServiceSelectionCriteria::defaults()
        ->withPreferredSoapVersion(SoapVersion::SOAP_11),
    $registry = EncoderRegistry::default()
);


$encoded = $driver->encode('Add', [
    (object)[
        'a' => 1,
        'b' => 2
    ]
]);

var_dump($encoded->getRequest());


$response = new SoapResponse(
    <<<EOXML
<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><soap:Body><AddResponse xmlns="http://tempuri.org/"><AddResult>3</AddResult></AddResponse></soap:Body></soap:Envelope>
EOXML
);

$decoded = $driver->decode('Add', $response);

var_dump($decoded);
