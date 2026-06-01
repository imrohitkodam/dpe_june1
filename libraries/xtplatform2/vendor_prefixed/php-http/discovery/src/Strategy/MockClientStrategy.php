<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

namespace XTP_BUILD\Http\Discovery\Strategy;

use XTP_BUILD\Http\Client\HttpAsyncClient;
use XTP_BUILD\Http\Client\HttpClient;
use XTP_BUILD\Http\Mock\Client as Mock;

/**
 * Find the Mock client.
 *
 * @author Sam Rapaport <me@samrapdev.com>
 */
final class MockClientStrategy implements DiscoveryStrategy
{
    /**
     * {@inheritdoc}
     */
    public static function getCandidates($type)
    {
        switch ($type) {
            case HttpClient::class:
            case HttpAsyncClient::class:
                return [['class' => Mock::class, 'condition' => Mock::class]];
            default:
                return [];
       }
    }
}
