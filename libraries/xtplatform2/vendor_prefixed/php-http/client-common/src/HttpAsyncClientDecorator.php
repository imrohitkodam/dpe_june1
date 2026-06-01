<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

namespace XTP_BUILD\Http\Client\Common;

use XTP_BUILD\Http\Client\HttpAsyncClient;
use XTP_BUILD\Psr\Http\Message\RequestInterface;

/**
 * Decorates an HTTP Async Client.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
trait HttpAsyncClientDecorator
{
    /**
     * @var HttpAsyncClient
     */
    protected $httpAsyncClient;

    /**
     * {@inheritdoc}
     *
     * @see HttpAsyncClient::sendAsyncRequest
     */
    public function sendAsyncRequest(RequestInterface $request)
    {
        return $this->httpAsyncClient->sendAsyncRequest($request);
    }
}
