<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

namespace XTP_BUILD\Http\Client\Common;

use XTP_BUILD\Http\Client\HttpAsyncClient;
use XTP_BUILD\Http\Client\HttpClient;
use XTP_BUILD\Psr\Http\Client\ClientInterface;

/**
 * A flexible http client, which implements both interface and will emulate
 * one contract, the other, or none at all depending on the injected client contract.
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
final class FlexibleHttpClient implements HttpClient, HttpAsyncClient
{
    use HttpClientDecorator;
    use HttpAsyncClientDecorator;

    /**
     * @param HttpClient|HttpAsyncClient|ClientInterface $client
     */
    public function __construct($client)
    {
        if (!($client instanceof HttpClient) && !($client instanceof HttpAsyncClient) && !($client instanceof ClientInterface)) {
            throw new \LogicException('Client must be an instance of Http\\Client\\HttpClient or Http\\Client\\HttpAsyncClient');
        }

        $this->httpClient = $client;
        $this->httpAsyncClient = $client;

        if (!($this->httpClient instanceof HttpClient) && !($client instanceof ClientInterface)) {
            $this->httpClient = new EmulatedHttpClient($this->httpClient);
        }

        if (!($this->httpAsyncClient instanceof HttpAsyncClient)) {
            $this->httpAsyncClient = new EmulatedHttpAsyncClient($this->httpAsyncClient);
        }
    }
}
