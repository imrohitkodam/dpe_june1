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
 * MediumChannelHelper.
 *
 * @since       1.0
 */
class MediumChannelHelper extends ChannelHelper
{
    protected $mediumClient;

    protected $integration_token;

    protected $is_auth;

    protected $me;

    /**
     * ChannelHelper.
     *
     * @param object $channel          params
     * @param string $integrationToken params
     */
    public function __construct($channel, $integrationToken = null)
    {
        parent::__construct($channel);

        if ($channel->id) {
            $this->integration_token = $this->channel->params->get('integration_token');
        }

        if ($integrationToken) {
            $this->integration_token = $integrationToken;
        }
    }

    /**
     * isAuth().
     *
     * @return bool
     */
    public function isAuth()
    {
        if (empty($this->integration_token)) {
            $this->integration_token = null;

            return false;
        }

        try {
            $this->getApiInstance();

            $user = $this->mediumClient->getAuthenticatedUser();

            if ($user->data) {
                return $user;
            }

            return false;
        } catch (Exception $e) {
            $logger = AutotweetLogger::getInstance();
            $logger->log(\Joomla\CMS\Log\Log::ERROR, $e->getMessage());

            // Just in case, it is shown someday
            \Joomla\CMS\Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

            // Invalidating access_token
            $ch->setToken($this->channel->id, 'integration_token', '');
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
        $logger->log(\Joomla\CMS\Log\Log::INFO, 'sendMediumMessage', $message);

        $user = $this->isAuth();

        if (!$user) {
            return [
                false,
                JText::_('COM_AUTOTWEET_CHANNEL_NOT_AUTH_ERR'),
            ];
        }

        $result = [false, 'Medium Unknown Error', null];

        try {
            $this->getApiInstance();
            $content = $this->renderPost($this->channel->id, 'pro.channels.medium-post', $message, $data);

            $request = [
                'title' => $data->title,
                'contentFormat' => 'html',
                'content' => $content,
                'publishStatus' => 'public',
                'canonicalUrl' => $data->org_url,
            ];
            $post = $this->mediumClient->createPost($user->data->id, $request);

            $messageId = $post->data->id;
            $url = $post->data->url;

            $result = [
                true,
            ];

            $result[] = 'OK - '.$messageId.' - '.$url;
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
     * getSocialUrl.
     *
     * @param object $user Param
     *
     * @return string
     */
    public function getSocialUrl($user)
    {
        return $user->data->url;
    }

    /**
     * Internal service functions.
     *
     * @return object
     */
    protected function getApiInstance()
    {
        if (!$this->mediumClient) {
            $this->mediumClient = new \XTS_BUILD\JonathanTorres\MediumSdk\Medium($this->integration_token);
        }

        return $this->mediumClient;
    }
}
