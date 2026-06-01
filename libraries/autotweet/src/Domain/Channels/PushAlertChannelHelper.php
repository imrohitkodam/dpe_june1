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
 * PushAlertChannelHelper.
 *
 * @since       1.0
 */
class PushAlertChannelHelper extends ChannelHelper
{
    protected $pushAlertClient;

    protected $restApiKey;

    /**
     * ChannelHelper.
     *
     * @param object $channel    params
     * @param string $restApiKey params
     */
    public function __construct($channel, $restApiKey = null)
    {
        parent::__construct($channel);

        if ($channel->id) {
            $this->restApiKey = $this->channel->params->get('rest_api_key');
        }

        if ($restApiKey) {
            $this->restApiKey = $restApiKey;
        }
    }

    /**
     * isAuth().
     *
     * @return bool
     */
    public function isAuth()
    {
        if (empty($this->restApiKey)) {
            $this->restApiKey = null;

            return false;
        }

        try {
            $this->getApiInstance();

            // Avoid Nyholm\Psr7 on Joomla 4
            // $factory = XTS_BUILD\Http\Discovery\MessageFactoryDiscovery::find();
            $factory = new XTS_BUILD\Http\Message\MessageFactory\GuzzleMessageFactory();
            $request = $factory->createRequest(
                'POST',
                $this->serviceUri,
                [
                    'Authorization' => 'api_key='.$this->restApiKey,
                ]
            );

            $response = $this->pushAlertClient->sendRequest($request);
            $httpStatusCode = $response->getStatusCode();

            // HTTP_STATUS_UNAUTHORIZED
            return 401 !== !$httpStatusCode;
        } catch (Exception $e) {
            $logger = AutotweetLogger::getInstance();
            $logger->log(\Joomla\CMS\Log\Log::ERROR, $e->getMessage());

            // Just in case, it is shown someday
            \Joomla\CMS\Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }

        return false;
    }

    /**
     * sendMessage.
     *
     * @param string $message Param
     * @param object $data    Params
     *
     * @return array
     */
    public function sendMessage($message, $data)
    {
        $logger = AutotweetLogger::getInstance();
        $logger->log(\Joomla\CMS\Log\Log::INFO, 'sendPushAlertMessage', $message);

        $result = [false, 'PushAlert Unknown Error', null];

        try {
            $this->getApiInstance();

            $title = \Joomla\CMS\Factory::getConfig()->get('sitename');

            $postVars = [
                'title' => $title,
                'message' => $message,
                'url' => $data->org_url,
            ];

            // Avoid Nyholm\Psr7 on Joomla 4
            // $factory = XTS_BUILD\Http\Discovery\MessageFactoryDiscovery::find();
            $factory = new XTS_BUILD\Http\Message\MessageFactory\GuzzleMessageFactory();

            $request = $factory->createRequest(
                'POST',
                $this->serviceUri,
                [
                    'Authorization' => 'api_key='.$this->restApiKey,
                ],
                http_build_query($postVars)
            );

            $response = $this->pushAlertClient->sendRequest($request);

            $httpStatusCode = $response->getStatusCode();
            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            if (200 === (int) $httpStatusCode) {
                $messageId = $data['id'];

                $result = [
                    true,
                ];
                $result[] = 'OK - '.$messageId;
            } else {
                $result = [
                    false,
                ];
                $result[] = $data['msg'];
            }
        } catch (Exception $e) {
            return [
                false,
                $e->getMessage(),
            ];
        }

        return $result;
    }

    /**
     * includeHashTags.
     *
     * @return bool
     */
    public function includeHashTags()
    {
        return $this->channel->params->get('hashtags', true);
    }

    /**
     * Internal service functions.
     *
     * @return object
     */
    protected function getApiInstance()
    {
        if (!$this->pushAlertClient) {
            $this->pushAlertClient = XTS_BUILD\Http\Discovery\HttpClientDiscovery::find();
            $this->serviceUri = new \XTS_BUILD\GuzzleHttp\Psr7\Uri('https://api.pushalert.co/rest/v1/send');
        }

        return $this->pushAlertClient;
    }
}
