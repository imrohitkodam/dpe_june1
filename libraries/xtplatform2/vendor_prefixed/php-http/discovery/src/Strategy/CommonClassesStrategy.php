<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

namespace XTP_BUILD\Http\Discovery\Strategy;

use XTP_BUILD\GuzzleHttp\Psr7\Request as GuzzleRequest;
use XTP_BUILD\Http\Client\HttpAsyncClient;
use XTP_BUILD\Http\Client\HttpClient;
use XTP_BUILD\Http\Discovery\MessageFactoryDiscovery;
use XTP_BUILD\Http\Message\MessageFactory;
use XTP_BUILD\Http\Message\MessageFactory\GuzzleMessageFactory;
use XTP_BUILD\Http\Message\StreamFactory;
use XTP_BUILD\Http\Message\StreamFactory\GuzzleStreamFactory;
use XTP_BUILD\Http\Message\UriFactory;
use XTP_BUILD\Http\Message\UriFactory\GuzzleUriFactory;
use XTP_BUILD\Http\Message\MessageFactory\DiactorosMessageFactory;
use XTP_BUILD\Http\Message\StreamFactory\DiactorosStreamFactory;
use XTP_BUILD\Http\Message\UriFactory\DiactorosUriFactory;
use XTP_BUILD\Psr\Http\Client\ClientInterface as Psr18Client;
use Zend\Diactoros\Request as DiactorosRequest;
use XTP_BUILD\Http\Message\MessageFactory\SlimMessageFactory;
use XTP_BUILD\Http\Message\StreamFactory\SlimStreamFactory;
use XTP_BUILD\Http\Message\UriFactory\SlimUriFactory;
use Slim\Http\Request as SlimRequest;
use XTP_BUILD\Http\Adapter\Guzzle6\Client as Guzzle6;
use XTP_BUILD\Http\Adapter\Guzzle5\Client as Guzzle5;
use XTP_BUILD\Http\Client\Curl\Client as Curl;
use XTP_BUILD\Http\Client\Socket\Client as Socket;
use XTP_BUILD\Http\Adapter\React\Client as React;
use XTP_BUILD\Http\Adapter\Buzz\Client as Buzz;
use XTP_BUILD\Http\Adapter\Cake\Client as Cake;
use XTP_BUILD\Http\Adapter\Zend\Client as Zend;
use XTP_BUILD\Http\Adapter\Artax\Client as Artax;
use Nyholm\Psr7\Factory\HttplugFactory as NyholmHttplugFactory;

/**
 * @internal
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class CommonClassesStrategy implements DiscoveryStrategy
{
    /**
     * @var array
     */
    private static $classes = [
        MessageFactory::class => [
            ['class' => NyholmHttplugFactory::class, 'condition' => [NyholmHttplugFactory::class]],
            ['class' => GuzzleMessageFactory::class, 'condition' => [GuzzleRequest::class, GuzzleMessageFactory::class]],
            ['class' => DiactorosMessageFactory::class, 'condition' => [DiactorosRequest::class, DiactorosMessageFactory::class]],
            ['class' => SlimMessageFactory::class, 'condition' => [SlimRequest::class, SlimMessageFactory::class]],
        ],
        StreamFactory::class => [
            ['class' => NyholmHttplugFactory::class, 'condition' => [NyholmHttplugFactory::class]],
            ['class' => GuzzleStreamFactory::class, 'condition' => [GuzzleRequest::class, GuzzleStreamFactory::class]],
            ['class' => DiactorosStreamFactory::class, 'condition' => [DiactorosRequest::class, DiactorosStreamFactory::class]],
            ['class' => SlimStreamFactory::class, 'condition' => [SlimRequest::class, SlimStreamFactory::class]],
        ],
        UriFactory::class => [
            ['class' => NyholmHttplugFactory::class, 'condition' => [NyholmHttplugFactory::class]],
            ['class' => GuzzleUriFactory::class, 'condition' => [GuzzleRequest::class, GuzzleUriFactory::class]],
            ['class' => DiactorosUriFactory::class, 'condition' => [DiactorosRequest::class, DiactorosUriFactory::class]],
            ['class' => SlimUriFactory::class, 'condition' => [SlimRequest::class, SlimUriFactory::class]],
        ],
        HttpAsyncClient::class => [
            ['class' => Guzzle6::class, 'condition' => Guzzle6::class],
            ['class' => Curl::class, 'condition' => Curl::class],
            ['class' => React::class, 'condition' => React::class],
        ],
        HttpClient::class => [
            ['class' => Guzzle6::class, 'condition' => Guzzle6::class],
            ['class' => Guzzle5::class, 'condition' => Guzzle5::class],
            ['class' => Curl::class, 'condition' => Curl::class],
            ['class' => Socket::class, 'condition' => Socket::class],
            ['class' => Buzz::class, 'condition' => Buzz::class],
            ['class' => React::class, 'condition' => React::class],
            ['class' => Cake::class, 'condition' => Cake::class],
            ['class' => Zend::class, 'condition' => Zend::class],
            ['class' => Artax::class, 'condition' => Artax::class],
            [
                'class' => [self::class, 'buzzInstantiate'],
                'condition' => [\Buzz\Client\FileGetContents::class, \Buzz\Message\ResponseBuilder::class],
            ],
        ],
        Psr18Client::class => [
            [
                'class' => [self::class, 'buzzInstantiate'],
                'condition' => [\Buzz\Client\FileGetContents::class, \Buzz\Message\ResponseBuilder::class],
            ],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public static function getCandidates($type)
    {
        if (Psr18Client::class === $type) {
            $candidates = self::$classes[PSR18Client::class];

            // HTTPlug 2.0 clients implements PSR18Client too.
            foreach (self::$classes[HttpClient::class] as $c) {
                if (is_subclass_of($c['class'], Psr18Client::class)) {
                    $candidates[] = $c;
                }
            }

            return $candidates;
        }

        if (isset(self::$classes[$type])) {
            return self::$classes[$type];
        }

        return [];
    }

    public static function buzzInstantiate()
    {
        return new \Buzz\Client\FileGetContents(MessageFactoryDiscovery::find());
    }
}
