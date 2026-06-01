<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

namespace XTP_BUILD\Http\Discovery;

use XTP_BUILD\Http\Client\HttpClient;
use XTP_BUILD\Http\Discovery\Exception\DiscoveryFailedException;

/**
 * Finds an HTTP Client.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
final class HttpClientDiscovery extends ClassDiscovery
{
    /**
     * Finds an HTTP Client.
     *
     * @return HttpClient
     *
     * @throws Exception\NotFoundException
     */
    public static function find()
    {
        try {
            $client = static::findOneByType(HttpClient::class);
        } catch (DiscoveryFailedException $e) {
            throw new NotFoundException(
                'No HTTPlug clients found. Make sure to install a package providing "php-http/client-implementation". Example: "php-http/guzzle6-adapter".',
                0,
                $e
            );
        }

        return static::instantiateClass($client);
    }
}
