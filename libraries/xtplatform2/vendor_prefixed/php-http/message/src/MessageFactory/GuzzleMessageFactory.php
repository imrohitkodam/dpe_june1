<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

namespace XTP_BUILD\Http\Message\MessageFactory;

use XTP_BUILD\GuzzleHttp\Psr7\Request;
use XTP_BUILD\GuzzleHttp\Psr7\Response;
use XTP_BUILD\Http\Message\MessageFactory;

/**
 * Creates Guzzle messages.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
final class GuzzleMessageFactory implements MessageFactory
{
    /**
     * {@inheritdoc}
     */
    public function createRequest(
        $method,
        $uri,
        array $headers = [],
        $body = null,
        $protocolVersion = '1.1'
    ) {
        return new Request(
            $method,
            $uri,
            $headers,
            $body,
            $protocolVersion
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createResponse(
        $statusCode = 200,
        $reasonPhrase = null,
        array $headers = [],
        $body = null,
        $protocolVersion = '1.1'
    ) {
        return new Response(
            $statusCode,
            $headers,
            $body,
            $protocolVersion,
            $reasonPhrase
        );
    }
}
