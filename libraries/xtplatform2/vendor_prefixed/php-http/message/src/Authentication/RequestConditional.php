<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

namespace XTP_BUILD\Http\Message\Authentication;

use XTP_BUILD\Http\Message\Authentication;
use XTP_BUILD\Http\Message\RequestMatcher;
use XTP_BUILD\Psr\Http\Message\RequestInterface;

/**
 * Authenticate a PSR-7 Request if the request is matching the given request matcher.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
final class RequestConditional implements Authentication
{
    /**
     * @var RequestMatcher
     */
    private $requestMatcher;

    /**
     * @var Authentication
     */
    private $authentication;

    /**
     * @param RequestMatcher $requestMatcher
     * @param Authentication $authentication
     */
    public function __construct(RequestMatcher $requestMatcher, Authentication $authentication)
    {
        $this->requestMatcher = $requestMatcher;
        $this->authentication = $authentication;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(RequestInterface $request)
    {
        if ($this->requestMatcher->matches($request)) {
            return $this->authentication->authenticate($request);
        }

        return $request;
    }
}
