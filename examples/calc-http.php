<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use GuzzleHttp\Client;
use Soap\Encoding\Driver;
use Soap\Engine\SimpleEngine;
use Soap\Psr18Transport\Psr18Transport;
use Soap\Wsdl\Loader\StreamWrapperLoader;
use Soap\WsdlReader\Wsdl1Reader;

$wsdlLocation = 'https://ecs.syr.edu/faculty/fawcett/Handouts/cse775/code/calcWebService/Calc.asmx?wsdl';

$engine = new SimpleEngine(
    Driver::createFromWsdl1((new Wsdl1Reader(new StreamWrapperLoader()))($wsdlLocation)),
    Psr18Transport::createForClient(
        new Client([
            'headers' => [
                'User-Agent' => 'testing/1.0',
            ],
        ])
    )
);

var_dump($engine->request('Add', [
    (object)[
        'a' => 1,
        'b' => 2
    ]
]));
