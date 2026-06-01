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

use XTS_BUILD\DirkGroenen\Pinterest\Pinterest;

/**
 * PinterestChannelHelper.
 *
 * @since       1.0
 */
class PinterestChannelHelper extends ChannelHelper
{
    protected $pinterest;

    protected $app_id;

    protected $app_secret;

    protected $access_token;

    protected $is_auth;

    protected $loginUrl;

    protected $me;

    protected $boards;

    /**
     * ChannelHelper.
     *
     * @param object $channel params
     */
    public function __construct($channel)
    {
        parent::__construct($channel);

        if ($channel->id) {
            $this->app_id = $this->channel->params->get('app_id');
            $this->app_secret = $this->channel->params->get('app_secret');

            $access_token = $this->channel->params->get('access_token');
            $this->setAccessToken($access_token);
        }
    }

    /**
     * setAccessToken.
     *
     * @param string $access_token Param
     */
    public function setAccessToken($access_token)
    {
        $this->access_token = $access_token;
    }

    /**
     * getAccessToken.
     *
     * @return string
     */
    public function getAccessToken()
    {
        return $this->access_token;
    }

    /**
     * isAuth().
     *
     * @return bool
     */
    public function isAuth()
    {
        $ch = XTF0FTable::getAnInstance('Channel', 'AutoTweetTable');
        $access_token = $this->getAccessToken();

        if (empty($access_token)) {
            $this->access_token = null;

            return false;
        }

        try {
            $this->getApiInstance();

            // First try
            $user = $this->getUser();

            if ($user) {
                return true;
            }

            throw new Exception('Error');
        } catch (Exception $e) {
            $logger = AutotweetLogger::getInstance();
            $logger->log(\Joomla\CMS\Log\Log::ERROR, $e->getMessage());

            // Just in case, it is shown someday
            \Joomla\CMS\Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

            // Invalidating access_token
            $ch->setToken($this->channel->id, 'access_token', '');
        }

        return false;
    }

    /**
     * authenticate.
     *
     * @param string $code Param
     *
     * @return bool
     */
    public function authenticate($code)
    {
        $this->getApiInstance();

        $token = $this->pinterest->auth->getOAuthToken($code);
        $this->access_token = $token->access_token;

        $ch = XTF0FTable::getAnInstance('Channel', 'AutoTweetTable');

        if ($this->access_token) {
            $ch->setToken($this->channel->id, 'access_token', $this->access_token);

            return true;
        }

        // Just in case, it will be shown someday
        \Joomla\CMS\Factory::getApplication()->enqueueMessage(JText::_('COM_AUTOTWEET_ERR_UNABLE_RETRIEVE'), 'error');

        // Invalidating access_token
        $ch->setToken($this->channel->id, 'access_token', '');

        return false;
    }

    /**
     * getAuthorizationUrl.
     *
     * @return string
     */
    public function getAuthorizationUrl()
    {
        $this->getApiInstance();

        return $this->loginUrl;
    }

    /**
     * getUser.
     *
     * @return object
     */
    public function getUser()
    {
        if (!$this->me) {
            $this->me = $this->pinterest->users->me();
        }

        return $this->me;
    }

    /**
     * getBoards.
     *
     * @return object
     */
    public function getBoards()
    {
        if (!$this->boards) {
            $this->boards = $this->pinterest->users->getMeBoards()->all();
        }

        return $this->boards;
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
        $isAuth = $this->isAuth();

        if (!$isAuth) {
            return [
                false,
                JText::_('COM_AUTOTWEET_CHANNEL_NOT_AUTH_ERR'),
            ];
        }

        $result = [false, 'Pinterest Unknown', null];

        try {
            if ($this->isMediaModeTextOnlyPost()) {
                return [
                    false,
                    'No image to pin.',
                ];
            }

            $imageUrl = $data->image_url;
            $originalUrl = $data->org_url;
            $boardid = $this->get('boardid');

            $pin = $this->pinterest->pins->create([
                'note' => $message,
                'link' => $originalUrl,
                'image_url' => $imageUrl,
                'board' => $boardid,
            ]);

            $msg = 'Pin id: '.$pin->id.' - '.$pin->url;
            $result = [
                true,
                $msg,
            ];
        } catch (Exception $e) {
            return [
                false,
                $e->getMessage(),
            ];
        }

        return $result;
    }

    /**
     * hasWeight.
     *
     * @return bool
     */
    public function hasWeight()
    {
        return true;
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
        return $user->link;
    }

    /**
     * Internal service functions.
     *
     * @return object
     */
    protected function getApiInstance()
    {
        if (!$this->pinterest) {
            $this->pinterest = new Pinterest($this->app_id, $this->app_secret);

            if ((isset($this->channel->id)) && ($this->channel->id)) {
                $url = $this->getCallbackUrl($this->channel->id);
                $this->loginUrl = $this->pinterest->auth->getLoginUrl($url, ['read_public', 'write_public']);
            }

            $accessToken = $this->getAccessToken();

            if (!empty($accessToken)) {
                if (is_object($accessToken)) {
                    $accessToken = (array) $accessToken;
                }

                $this->pinterest->auth->setOAuthToken($accessToken);
            }
        }

        return $this->pinterest;
    }

    private function getCallbackUrl($channelId, $callback = 'callback')
    {
        return \Joomla\CMS\Uri\Uri::base().'index.php?option=com_autotweet';
    }
}
