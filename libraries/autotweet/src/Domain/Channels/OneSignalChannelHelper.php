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
 * OneSignalChannelHelper.
 *
 * @since       1.0
 */
class OneSignalChannelHelper extends ChannelHelper
{
    protected $oneSignalClient;

    protected $appId;

    protected $restApiKey;

    protected $userAuthKey;

    protected $is_auth;

    /**
     * ChannelHelper.
     *
     * @param object $channel     params
     * @param string $appId       params
     * @param string $restApiKey  params
     * @param string $userAuthKey params
     */
    public function __construct($channel, $appId = null, $restApiKey = null, $userAuthKey = null)
    {
        parent::__construct($channel);

        if ($channel->id) {
            $this->appId = $this->channel->params->get('app_id');
            $this->restApiKey = $this->channel->params->get('rest_api_key');
            $this->userAuthKey = $this->channel->params->get('user_auth_key');
        }

        if ($appId) {
            $this->appId = $appId;
            $this->restApiKey = $restApiKey;
            $this->userAuthKey = $userAuthKey;
        }
    }

    /**
     * isAuth().
     *
     * @return bool
     */
    public function isAuth()
    {
        if (empty($this->appId)) {
            $this->appId = null;

            return false;
        }

        try {
            $this->getApiInstance();
            $devices = $this->oneSignalClient->devices->getAll();

            return true;
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
        $logger->log(\Joomla\CMS\Log\Log::INFO, 'sendOneSignalMessage', $message);

        $isAuth = $this->isAuth();

        if (!$isAuth) {
            return [
                false,
                JText::_('COM_AUTOTWEET_CHANNEL_NOT_AUTH_ERR'),
            ];
        }

        $result = [false, 'OneSignal Unknown Error', null];

        try {
            $this->getApiInstance();

            $title = \Joomla\CMS\Factory::getConfig()->get('sitename');

            $notification = [
                'contents' => [
                    'en' => $message,
                ],
                'headings' => [
                    'en' => $title,
                ],
                'included_segments' => ['All'],
            ];

            // Web Push
            if ($this->channel->params->get('chrome')) {
                $notification['isChromeWeb'] = true;
            }

            if ($this->channel->params->get('firefox')) {
                $notification['isFirefox'] = true;
            }

            if ($this->channel->params->get('safari')) {
                $notification['isSafari'] = true;
            }

            // Push Notifications
            if ($this->channel->params->get('ios')) {
                $notification['isIos'] = true;
            }

            if ($this->channel->params->get('android')) {
                $notification['isAndroid'] = true;
            }

            if ($this->channel->params->get('adm')) {
                $notification['isAdm'] = true;
            }

            if ($this->channel->params->get('wp')) {
                $notification['isWP'] = true;
                $notification['isWP_WNS'] = true;
            }

            if (!empty($data->org_url)) {
                $notification['url'] = $data->org_url;
            }

            if (($this->isMediaModePostWithImage()) && (!empty($data->image_url))) {
                if ($this->channel->params->get('android')) {
                    $notification['big_picture'] = $data->image_url;
                }

                if ($this->channel->params->get('adm')) {
                    $notification['adm_big_picture'] = $data->image_url;
                }

                if ($this->channel->params->get('chrome')) {
                    $notification['chrome_big_picture'] = $data->image_url;
                    $notification['chrome_web_image'] = $data->image_url;
                }
            }

            // $logger->log(\Joomla\CMS\Log\Log::INFO, 'sendOneSignalMessage notification:', $notification);

            $response = $this->oneSignalClient->notifications->add($notification);
            $messageId = $response['id'];

            if (empty($messageId)) {
                $errors = $response['errors'];
                $error = array_pop($errors);

                $result = [
                    false,
                ];
                $result[] = $error;
            } else {
                $result = [
                    true,
                ];
                $result[] = 'OK - '.$messageId;
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
        if (!$this->oneSignalClient) {
            $config = new \XTS_BUILD\OneSignal\Config();
            $config->setApplicationId($this->appId);
            $config->setApplicationAuthKey($this->restApiKey);
            $config->setUserAuthKey($this->userAuthKey);

            $guzzleMessageFactory = new \XTS_BUILD\Http\Message\MessageFactory\GuzzleMessageFactory();

            $guzzle = new \XTS_BUILD\GuzzleHttp\Client();
            $guzzleAdapter = new \XTS_BUILD\Http\Adapter\Guzzle6\Client($guzzle);

            $client = new \XTS_BUILD\Http\Client\Common\HttpMethodsClient($guzzleAdapter, $guzzleMessageFactory);
            $this->oneSignalClient = new \XTS_BUILD\OneSignal\OneSignal($config, $client);
        }

        return $this->oneSignalClient;
    }
}
