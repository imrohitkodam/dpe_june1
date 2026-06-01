<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */

namespace XTS_BUILD\Http\Message\StreamFactory;

use XTS_BUILD\GuzzleHttp\Psr7\Utils;
use XTS_BUILD\Http\Message\StreamFactory;

/**
 * Creates Guzzle streams.
 *
 * @author Михаил Красильников <m.krasilnikov@yandex.ru>
 *
 * @deprecated This will be removed in php-http/message2.0. Consider using the official Guzzle PSR-17 factory
 */
final class GuzzleStreamFactory implements StreamFactory
{
    /**
     * {@inheritdoc}
     */
    public function createStream($body = null)
    {
        if (class_exists(Utils::class)) {
            return Utils::streamFor($body);
        }

        return \XTS_BUILD\GuzzleHttp\Psr7\stream_for($body);
    }
}
