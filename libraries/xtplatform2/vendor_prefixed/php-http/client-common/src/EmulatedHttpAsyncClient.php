<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

namespace XTP_BUILD\Http\Client\Common;

use XTP_BUILD\Http\Client\HttpAsyncClient;
use XTP_BUILD\Http\Client\HttpClient;
use XTP_BUILD\Psr\Http\Client\ClientInterface;

/**
 * Emulates an async HTTP client.
 *
 * This should be replaced by an anonymous class in PHP 7.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
class EmulatedHttpAsyncClient implements HttpClient, HttpAsyncClient
{
    use HttpAsyncClientEmulator;
    use HttpClientDecorator;

    /**
     * @param HttpClient|ClientInterface $httpClient
     */
    public function __construct($httpClient)
    {
        if (!($httpClient instanceof HttpClient) && !($httpClient instanceof ClientInterface)) {
            throw new \LogicException('Client must be an instance of Http\\Client\\HttpClient or Psr\\Http\\Client\\ClientInterface');
        }

        $this->httpClient = $httpClient;
    }
}
