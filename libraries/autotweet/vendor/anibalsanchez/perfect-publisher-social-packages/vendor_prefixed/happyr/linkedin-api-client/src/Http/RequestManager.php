<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */

namespace XTS_BUILD\Happyr\LinkedIn\Http;

use XTS_BUILD\Happyr\LinkedIn\Exception\LinkedInTransferException;
use XTS_BUILD\Http\Client\Exception\TransferException;
use XTS_BUILD\Http\Client\HttpClient;
use XTS_BUILD\Http\Discovery\HttpClientDiscovery;
use XTS_BUILD\Http\Discovery\MessageFactoryDiscovery;
use XTS_BUILD\Http\Message\MessageFactory;

/**
 * A class to create HTTP requests and to send them.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class RequestManager implements RequestManagerInterface
{
    /**
     * @var \Http\Client\HttpClient
     */
    private $httpClient;

    /**
     * @var \Http\Message\MessageFactory
     */
    private $messageFactory;

    /**
     * {@inheritdoc}
     */
    public function sendRequest($method, $uri, array $headers = [], $body = null, $protocolVersion = '1.1')
    {
        $request = $this->getMessageFactory()->createRequest($method, $uri, $headers, $body, $protocolVersion);

        try {
            return $this->getHttpClient()->sendRequest($request);
        } catch (TransferException $e) {
            throw new LinkedInTransferException('Error while requesting data from LinkedIn.com: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setHttpClient(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    /**
     * @return HttpClient
     */
    protected function getHttpClient()
    {
        if ($this->httpClient === null) {
            $this->httpClient = HttpClientDiscovery::find();
        }

        return $this->httpClient;
    }

    /**
     * @param MessageFactory $messageFactory
     *
     * @return RequestManager
     */
    public function setMessageFactory(MessageFactory $messageFactory)
    {
        $this->messageFactory = $messageFactory;

        return $this;
    }

    /**
     * @return \Http\Message\MessageFactory
     */
    private function getMessageFactory()
    {
        if ($this->messageFactory === null) {
            $this->messageFactory = MessageFactoryDiscovery::find();
        }

        return $this->messageFactory;
    }
}
