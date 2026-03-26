<?php

declare(strict_types=1);

namespace Soap\Encoding\Benchmarks;

/**
 * SoapClient override that captures requests and returns mock responses.
 * Used by benchmarks to measure ext-soap encode/decode without network I/O.
 *
 * @internal
 */
class BenchSoapClient extends \SoapClient
{
    public ?string $mockResponse = null;

    public function __doRequest(string $request, string $location, string $action, int $version, bool $oneWay = false, ?string $uriParserClass = null): ?string
    {
        return $this->mockResponse;
    }
}
