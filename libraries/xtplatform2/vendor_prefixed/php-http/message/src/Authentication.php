<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

namespace XTP_BUILD\Http\Message;

use XTP_BUILD\Psr\Http\Message\RequestInterface;

/**
 * Authenticate a PSR-7 Request.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
interface Authentication
{
    /**
     * Authenticates a request.
     *
     * @param RequestInterface $request
     *
     * @return RequestInterface
     */
    public function authenticate(RequestInterface $request);
}
