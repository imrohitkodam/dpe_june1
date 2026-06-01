<?php

/*
 * @package     Perfect Publisher
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 */

defined('_JEXEC') || exit;

/**
 * AutotweetBitlyService - AutoTweet bit.ly url short service.
 *
 * @since       1.0
 */
class AutotweetBitlyService extends AutotweetShortservice
{
    /**
     * getShortURL.
     *
     * @param string $longUrl param
     *
     * @return string
     */
    public function getShortUrl($longUrl)
    {
        $bitAccessToken = $this->data['bit_access_token'];

        $httpClient = XTS_BUILD\Http\Discovery\HttpClientDiscovery::find();

        // Avoid Nyholm\Psr7 on Joomla 4
        // $messageFactory = XTS_BUILD\Http\Discovery\MessageFactoryDiscovery::find();
        $messageFactory = new XTS_BUILD\Http\Message\MessageFactory\GuzzleMessageFactory();

        $uri = new \XTS_BUILD\GuzzleHttp\Psr7\Uri('https://api-ssl.bitly.com/v4/user');
        $request = $messageFactory->createRequest(
            'GET',
            $uri,
            [
                'Authorization' => 'Bearer '.$bitAccessToken,
            ]
        );

        $response = $httpClient->sendRequest($request);
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        $result = json_decode($body);

        if (200 !== $statusCode) {
            $this->error_msg = $result->message.' - '.$result->description;

            return null;
        }

        $message = new stdClass();
        $message->long_url = $longUrl;
        $message->domain = 'bit.ly';
        $message->group_guid = $result->default_group_guid;
        $package = json_encode($message);

        $uri = new \XTS_BUILD\GuzzleHttp\Psr7\Uri('https://api-ssl.bitly.com/v4/shorten');
        $request = $messageFactory->createRequest(
            'POST',
            $uri,
            [
                'Authorization' => 'Bearer '.$bitAccessToken,
            ],
            $package
        );

        $response = $httpClient->sendRequest($request);
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        $result = json_decode($body);

        if (200 === $statusCode || 201 === $statusCode) {
            return $result->link;
        }

        $this->error_msg = $result->message.' - '.$result->description;

        return null;
    }
}
