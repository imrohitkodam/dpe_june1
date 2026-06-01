<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */

namespace XTS_BUILD\Http\Discovery\Strategy;

use XTS_BUILD\Psr\Http\Message\RequestFactoryInterface;
use XTS_BUILD\Psr\Http\Message\ResponseFactoryInterface;
use XTS_BUILD\Psr\Http\Message\ServerRequestFactoryInterface;
use XTS_BUILD\Psr\Http\Message\StreamFactoryInterface;
use XTS_BUILD\Psr\Http\Message\UploadedFileFactoryInterface;
use XTS_BUILD\Psr\Http\Message\UriFactoryInterface;

/**
 * @internal
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class CommonPsr17ClassesStrategy implements DiscoveryStrategy
{
    /**
     * @var array
     */
    private static $classes = [
        RequestFactoryInterface::class => [
            'Phalcon\Http\Message\RequestFactory',
            'Nyholm\Psr7\Factory\Psr17Factory',
            'Zend\Diactoros\RequestFactory',
            'XTS_BUILD\GuzzleHttp\Psr7\HttpFactory',
            'XTS_BUILD\Http\Factory\Diactoros\RequestFactory',
            'XTS_BUILD\Http\Factory\Guzzle\RequestFactory',
            'XTS_BUILD\Http\Factory\Slim\RequestFactory',
            'Laminas\Diactoros\RequestFactory',
            'Slim\Psr7\Factory\RequestFactory',
        ],
        ResponseFactoryInterface::class => [
            'Phalcon\Http\Message\ResponseFactory',
            'Nyholm\Psr7\Factory\Psr17Factory',
            'Zend\Diactoros\ResponseFactory',
            'XTS_BUILD\GuzzleHttp\Psr7\HttpFactory',
            'XTS_BUILD\Http\Factory\Diactoros\ResponseFactory',
            'XTS_BUILD\Http\Factory\Guzzle\ResponseFactory',
            'XTS_BUILD\Http\Factory\Slim\ResponseFactory',
            'Laminas\Diactoros\ResponseFactory',
            'Slim\Psr7\Factory\ResponseFactory',
        ],
        ServerRequestFactoryInterface::class => [
            'Phalcon\Http\Message\ServerRequestFactory',
            'Nyholm\Psr7\Factory\Psr17Factory',
            'Zend\Diactoros\ServerRequestFactory',
            'XTS_BUILD\GuzzleHttp\Psr7\HttpFactory',
            'XTS_BUILD\Http\Factory\Diactoros\ServerRequestFactory',
            'XTS_BUILD\Http\Factory\Guzzle\ServerRequestFactory',
            'XTS_BUILD\Http\Factory\Slim\ServerRequestFactory',
            'Laminas\Diactoros\ServerRequestFactory',
            'Slim\Psr7\Factory\ServerRequestFactory',
        ],
        StreamFactoryInterface::class => [
            'Phalcon\Http\Message\StreamFactory',
            'Nyholm\Psr7\Factory\Psr17Factory',
            'Zend\Diactoros\StreamFactory',
            'XTS_BUILD\GuzzleHttp\Psr7\HttpFactory',
            'XTS_BUILD\Http\Factory\Diactoros\StreamFactory',
            'XTS_BUILD\Http\Factory\Guzzle\StreamFactory',
            'XTS_BUILD\Http\Factory\Slim\StreamFactory',
            'Laminas\Diactoros\StreamFactory',
            'Slim\Psr7\Factory\StreamFactory',
        ],
        UploadedFileFactoryInterface::class => [
            'Phalcon\Http\Message\UploadedFileFactory',
            'Nyholm\Psr7\Factory\Psr17Factory',
            'Zend\Diactoros\UploadedFileFactory',
            'XTS_BUILD\GuzzleHttp\Psr7\HttpFactory',
            'XTS_BUILD\Http\Factory\Diactoros\UploadedFileFactory',
            'XTS_BUILD\Http\Factory\Guzzle\UploadedFileFactory',
            'XTS_BUILD\Http\Factory\Slim\UploadedFileFactory',
            'Laminas\Diactoros\UploadedFileFactory',
            'Slim\Psr7\Factory\UploadedFileFactory',
        ],
        UriFactoryInterface::class => [
            'Phalcon\Http\Message\UriFactory',
            'Nyholm\Psr7\Factory\Psr17Factory',
            'Zend\Diactoros\UriFactory',
            'XTS_BUILD\GuzzleHttp\Psr7\HttpFactory',
            'XTS_BUILD\Http\Factory\Diactoros\UriFactory',
            'XTS_BUILD\Http\Factory\Guzzle\UriFactory',
            'XTS_BUILD\Http\Factory\Slim\UriFactory',
            'Laminas\Diactoros\UriFactory',
            'Slim\Psr7\Factory\UriFactory',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public static function getCandidates($type)
    {
        $candidates = [];
        if (isset(self::$classes[$type])) {
            foreach (self::$classes[$type] as $class) {
                $candidates[] = ['class' => $class, 'condition' => [$class]];
            }
        }

        return $candidates;
    }
}
