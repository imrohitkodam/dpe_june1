<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

namespace XTP_BUILD\Http\Client\Curl;

use XTP_BUILD\Http\Message\Builder\ResponseBuilder as OriginalResponseBuilder;
use XTP_BUILD\Psr\Http\Message\ResponseInterface;

/**
 * Extended response builder.
 */
class ResponseBuilder extends OriginalResponseBuilder
{
    /**
     * Replace response with a new instance.
     *
     * @param ResponseInterface $response
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
    }
}
