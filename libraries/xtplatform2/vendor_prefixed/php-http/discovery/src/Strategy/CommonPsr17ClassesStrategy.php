<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

namespace XTP_BUILD\Http\Discovery\Strategy;

use XTP_BUILD\Psr\Http\Message\RequestFactoryInterface;
use XTP_BUILD\Psr\Http\Message\ResponseFactoryInterface;
use XTP_BUILD\Psr\Http\Message\ServerRequestFactoryInterface;
use XTP_BUILD\Psr\Http\Message\StreamFactoryInterface;
use XTP_BUILD\Psr\Http\Message\UploadedFileFactoryInterface;
use XTP_BUILD\Psr\Http\Message\UriFactoryInterface;

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
            'Nyholm\Psr7\Factory\Psr17Factory',
            'Zend\Diactoros\RequestFactory',
            'XTP_BUILD\Http\Factory\Diactoros\RequestFactory',
            'XTP_BUILD\Http\Factory\Guzzle\RequestFactory',
            'XTP_BUILD\Http\Factory\Slim\RequestFactory',
        ],
        ResponseFactoryInterface::class => [
            'Nyholm\Psr7\Factory\Psr17Factory',
            'Zend\Diactoros\ResponseFactory',
            'XTP_BUILD\Http\Factory\Diactoros\ResponseFactory',
            'XTP_BUILD\Http\Factory\Guzzle\ResponseFactory',
            'XTP_BUILD\Http\Factory\Slim\ResponseFactory',
        ],
        ServerRequestFactoryInterface::class => [
            'Nyholm\Psr7\Factory\Psr17Factory',
            'Zend\Diactoros\ServerRequestFactory',
            'XTP_BUILD\Http\Factory\Diactoros\ServerRequestFactory',
            'XTP_BUILD\Http\Factory\Guzzle\ServerRequestFactory',
            'XTP_BUILD\Http\Factory\Slim\ServerRequestFactory',
        ],
        StreamFactoryInterface::class => [
            'Nyholm\Psr7\Factory\Psr17Factory',
            'Zend\Diactoros\StreamFactory',
            'XTP_BUILD\Http\Factory\Diactoros\StreamFactory',
            'XTP_BUILD\Http\Factory\Guzzle\StreamFactory',
            'XTP_BUILD\Http\Factory\Slim\StreamFactory',
        ],
        UploadedFileFactoryInterface::class => [
            'Nyholm\Psr7\Factory\Psr17Factory',
            'Zend\Diactoros\UploadedFileFactory',
            'XTP_BUILD\Http\Factory\Diactoros\UploadedFileFactory',
            'XTP_BUILD\Http\Factory\Guzzle\UploadedFileFactory',
            'XTP_BUILD\Http\Factory\Slim\UploadedFileFactory',
        ],
        UriFactoryInterface::class => [
            'Nyholm\Psr7\Factory\Psr17Factory',
            'Zend\Diactoros\UriFactory',
            'XTP_BUILD\Http\Factory\Diactoros\UriFactory',
            'XTP_BUILD\Http\Factory\Guzzle\UriFactory',
            'XTP_BUILD\Http\Factory\Slim\UriFactory',
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
