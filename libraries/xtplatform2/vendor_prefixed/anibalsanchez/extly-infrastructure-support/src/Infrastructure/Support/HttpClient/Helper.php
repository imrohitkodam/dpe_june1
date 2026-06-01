<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

/*
 * @package     Extly Infrastructure Support
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2021 Extly, CB. All rights reserved.
 * @license     http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @see         https://www.extly.com
 */

namespace XTP_BUILD\Extly\Infrastructure\Support\HttpClient;

use XTP_BUILD\Extly\Infrastructure\Creator\CreatorTrait;
use XTP_BUILD\Extly\Infrastructure\Support\SupportException;
use XTP_BUILD\Http\Client\Common\HttpMethodsClient;
use XTP_BUILD\Http\Client\Common\Plugin\RedirectPlugin;
use XTP_BUILD\Http\Client\Common\Plugin\RetryPlugin;
use XTP_BUILD\Http\Client\Common\PluginClient;
use XTP_BUILD\Http\Discovery\HttpClientDiscovery;
use XTP_BUILD\Http\Discovery\MessageFactoryDiscovery;
use XTP_BUILD\Http\Message\Authentication\BasicAuth;

final class Helper
{
    use CreatorTrait;

    private $authentication;

    public function authWithBasicAuth($username, $password)
    {
        $this->authentication = new BasicAuth($username, $password);

        return $this;
    }

    public function get($uri, $processAllHttpCases = true)
    {
        try {
            if ($processAllHttpCases) {
                return $this->checkResponse($this->processAllHttpCases($uri));
            }

            return $this->checkResponse($this->rawHttpGet($uri));
        } catch (\Exception $e) {
            throw new SupportException($e->getMessage());
        }
    }

    public function getLocationHeader($response)
    {
        if ($response->hasHeader('Location')) {
            return $response->getHeader('Location');
        }

        return null;
    }

    public function isOk($response)
    {
        $httpStatusCode = $response->getStatusCode();

        return StatusCodeEnum::HTTP_STATUS_OK === $httpStatusCode;
    }

    public function isRedirection($response)
    {
        $httpStatusCode = $response->getStatusCode();

        return ($httpStatusCode >= StatusCodeEnum::HTTP_STATUS_MOVED_PERMANENTLY)
            && ($httpStatusCode <= StatusCodeEnum::HTTP_STATUS_PERMANENT_REDIRECT);
    }

    public function rawHttpGet($uri)
    {
        $client = new HttpMethodsClient(
            HttpClientDiscovery::find(),
            MessageFactoryDiscovery::find()
        );

        return $client->get($uri);
    }

    private function processAllHttpCases($uri)
    {
        $request = MessageFactoryDiscovery::find()
            ->createRequest(RequestMethodEnum::GET, $uri);

        if ($this->authentication) {
            $request = $this->authentication->authenticate($request);
        }

        return $this->getRedirectHttpClient()->sendRequest($request);
    }

    private function getRedirectHttpClient()
    {
        $retryPlugin = new RetryPlugin();
        $redirectPlugin = new RedirectPlugin();

        return new PluginClient(
            HttpClientDiscovery::find(),
            [
                $retryPlugin,
                $redirectPlugin,
            ]
        );
    }

    private function checkResponse($response)
    {
        if ($this->isOk($response)) {
            return $response;
        }

        $httpStatusCode = $response->getStatusCode();

        throw new SupportException(StatusCodeEnum::search($httpStatusCode), $httpStatusCode);
    }
}
