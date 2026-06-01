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
 * PushwooshChannelHelper.
 *
 * @since       1.0
 */
class PushwooshChannelHelper extends ChannelHelper
{
    protected $pushwooshClient;

    protected $applicationId;

    protected $accessToken;

    /**
     * ChannelHelper.
     *
     * @param object $channel       params
     * @param string $applicationId params
     * @param string $accessToken   params
     */
    public function __construct($channel, $applicationId = null, $accessToken = null)
    {
        parent::__construct($channel);

        if ($channel->id) {
            $this->applicationId = $this->channel->params->get('application_id');
            $this->accessToken = $this->channel->params->get('access_token');
        }

        if ($applicationId) {
            $this->applicationId = $applicationId;
            $this->accessToken = $accessToken;
        }
    }

    /**
     * isAuth().
     *
     * @return bool
     */
    public function isAuth()
    {
        if (empty($this->applicationId)) {
            $this->applicationId = null;

            return false;
        }

        try {
            $this->getApiInstance();

            /*
            $request = XTS_BUILD\Gomoob\Pushwoosh\Model\Request\GetNearestZoneRequest::create()
                ->setHwid('HWID')
                ->setLat(10.12345)
                ->setLng(28.12345);
            $response = $this->pushwooshClient->getNearestZone($request);
            */

            $request = XTS_BUILD\Gomoob\Pushwoosh\Model\Request\GetTagsRequest::create()
                ->setHwid('HWID');
            $response = $this->pushwooshClient->GetTags($request);
            $statusCode = $response->getStatusCode();

            return ($statusCode >= 200) && ($statusCode < 300);
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
        $logger->log(\Joomla\CMS\Log\Log::INFO, 'sendPushwooshMessage', $message);

        $isAuth = $this->isAuth();

        if (!$isAuth) {
            return [
                false,
                JText::_('COM_AUTOTWEET_CHANNEL_NOT_AUTH_ERR'),
            ];
        }

        $result = [false, 'Pushwoosh Unknown Error', null];

        try {
            $this->getApiInstance();

            $title = \Joomla\CMS\Factory::getConfig()->get('sitename');

            $notification = XTS_BUILD\Gomoob\Pushwoosh\Model\Notification\Notification::create()
                ->setContent($message);

            if (!empty($data->org_url)) {
                $notification->setLink($data->org_url);
            }

            // Web Push
            if ($this->channel->params->get('chrome')) {
                $notification->addPlatform(XTS_BUILD\Gomoob\Pushwoosh\Model\Notification\Platform::chrome());

                $chrome = XTS_BUILD\Gomoob\Pushwoosh\Model\Notification\Chrome::create()
                    ->setTitle($title);

                if (($this->isMediaModePostWithImage()) && (!empty($data->image_url))) {
                    $chrome->setImage($data->image_url);
                }

                $notification->setChrome($chrome);
            }

            if ($this->channel->params->get('firefox')) {
                $notification->addPlatform(XTS_BUILD\Gomoob\Pushwoosh\Model\Notification\Platform::firefox());
                $notification->setFirefox(
                    XTS_BUILD\Gomoob\Pushwoosh\Model\Notification\Firefox::create()
                        ->setTitle($title)
                );
            }

            if ($this->channel->params->get('safari')) {
                $notification->addPlatform(XTS_BUILD\Gomoob\Pushwoosh\Model\Notification\Platform::safari());
                $notification->setSafari(
                    XTS_BUILD\Gomoob\Pushwoosh\Model\Notification\Safari::create()
                        ->setTitle($title)
                );
            }

            // Push Notifications
            if ($this->channel->params->get('ios')) {
                $notification->addPlatform(XTS_BUILD\Gomoob\Pushwoosh\Model\Notification\Platform::iOS());
                $notification->setIOS(
                    XTS_BUILD\Gomoob\Pushwoosh\Model\Notification\IOS::create()
                );
            }

            if ($this->channel->params->get('android')) {
                $notification->addPlatform(XTS_BUILD\Gomoob\Pushwoosh\Model\Notification\Platform::android());

                $android = XTS_BUILD\Gomoob\Pushwoosh\Model\Notification\Android::create();
                $android->setHeader($title);

                if (($this->isMediaModePostWithImage()) && (!empty($data->image_url))) {
                    $android->setBanner($data->image_url);
                }

                $notification->setAndroid($android);
            }

            if ($this->channel->params->get('adm')) {
                $notification->addPlatform(XTS_BUILD\Gomoob\Pushwoosh\Model\Notification\Platform::amazon());

                $amazon = XTS_BUILD\Gomoob\Pushwoosh\Model\Notification\ADM::create();
                $amazon->setHeader($title);

                if (($this->isMediaModePostWithImage()) && (!empty($data->image_url))) {
                    $amazon->setBanner($data->image_url);
                }

                $notification->setADM($amazon);
            }

            if ($this->channel->params->get('wp')) {
                $notification->addPlatform(XTS_BUILD\Gomoob\Pushwoosh\Model\Notification\Platform::windowsPhone7());
                $notification->addPlatform(XTS_BUILD\Gomoob\Pushwoosh\Model\Notification\Platform::windows8());

                $notification->setWP(
                    XTS_BUILD\Gomoob\Pushwoosh\Model\Notification\WP::create()
                );

                $notification->setWNS(
                    XTS_BUILD\Gomoob\Pushwoosh\Model\Notification\WNS::create()
                );
            }

            // Create a request for the '/createMessage' Web Service
            $request = XTS_BUILD\Gomoob\Pushwoosh\Model\Request\CreateMessageRequest::create()
                ->addNotification($notification);

            // Call the REST Web Service
            $createMessageResponse = $this->pushwooshClient->createMessage($request);

            $ok = $createMessageResponse->isOk();
            $statusCode = $createMessageResponse->getStatusCode();
            $statusMessage = $createMessageResponse->getStatusMessage();

            if ($ok) {
                $createMessageResponseResponse = $createMessageResponse->getResponse();
                $messages = $createMessageResponseResponse->getMessages();
                $message = array_pop($messages);

                $result = [
                    true,
                ];

                $result[] = $statusMessage.' - '.$message;
            } else {
                $result = [
                    false,
                ];

                $result[] = $statusCode.' - '.$statusMessage;
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
        if (!$this->pushwooshClient) {
            $this->pushwooshClient = XTS_BUILD\Gomoob\Pushwoosh\Client\Pushwoosh::create()
                ->setApplication($this->applicationId)
                ->setAuth($this->accessToken);
        }

        return $this->pushwooshClient;
    }
}
