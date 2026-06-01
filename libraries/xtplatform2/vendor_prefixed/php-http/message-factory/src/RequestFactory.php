<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

namespace XTP_BUILD\Http\Message;

use XTP_BUILD\Psr\Http\Message\UriInterface;
use XTP_BUILD\Psr\Http\Message\RequestInterface;
use XTP_BUILD\Psr\Http\Message\StreamInterface;

/**
 * Factory for PSR-7 Request.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
interface RequestFactory
{
    /**
     * Creates a new PSR-7 request.
     *
     * @param string                               $method
     * @param string|UriInterface                  $uri
     * @param array                                $headers
     * @param resource|string|StreamInterface|null $body
     * @param string                               $protocolVersion
     *
     * @return RequestInterface
     */
    public function createRequest(
        $method,
        $uri,
        array $headers = [],
        $body = null,
        $protocolVersion = '1.1'
    );
}
