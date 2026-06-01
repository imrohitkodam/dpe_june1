<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */

namespace XTS_BUILD\Http\Client\Common;

use XTS_BUILD\Http\Client\HttpAsyncClient;
use XTS_BUILD\Http\Client\HttpClient;
use XTS_BUILD\Psr\Http\Client\ClientInterface;

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
