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
 * BloggerChannelHelper.
 *
 * @since       1.0
 */
class BloggerChannelHelper extends ChannelHelper
{
    protected $bloggerClient;

    protected $blogger;

    protected $client_id;

    protected $client_secret;

    protected $developer_key;

    protected $access_token;

    protected $is_auth;

    protected $me;

    protected $blogs;

    /**
     * ChannelHelper.
     *
     * @param object $channel params
     */
    public function __construct($channel)
    {
        parent::__construct($channel);

        if ($channel->id) {
            $this->client_id = $this->channel->params->get('client_id');
            $this->client_secret = $this->channel->params->get('client_secret');
            $this->developer_key = $this->channel->params->get('developer_key');

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
            $isExpired = $this->bloggerClient->isAccessTokenExpired();

            if (!$isExpired) {
                $user = $this->getUser();

                return true;
            }

            $this->_refreshToken();

            // Second try, and the last
            $isExpired = $this->bloggerClient->isAccessTokenExpired();

            if ($isExpired) {
                // Invalidating access_token
                $ch->setToken($this->channel->id, 'access_token', '');

                // Just in case, it is shown someday
                \Joomla\CMS\Factory::getApplication()->enqueueMessage(JText::_('COM_AUTOTWEET_ERR_TOKEN_EXPIRED'), 'error');
            } else {
                $user = $this->getUser();

                // We Ok, and it's new one!
                $ch->setToken($this->channel->id, 'access_token', $this->getAccessToken());

                return true;
            }
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

        $this->access_token = $this->bloggerClient->authenticate($code);

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

        return $this->bloggerClient->createAuthUrl();
    }

    /**
     * getUser.
     *
     * @return object
     */
    public function getUser()
    {
        if (!$this->me) {
            $this->me = $this->blogger->users->get('self');
        }

        return $this->me;
    }

    /**
     * getUser.
     *
     * @return object
     */
    public function getBlogs()
    {
        if (!$this->blogs) {
            $this->blogs = $this->blogger->blogs->listByUser('self');
        }

        return $this->blogs;
    }

    /**
     * getExpiresIn.
     *
     * @return string
     */
    public function getExpiresIn()
    {
        if (($this->access_token) && (!empty($this->access_token))) {
            if (is_string($this->access_token)) {
                $access_token = json_decode($this->access_token);
            } else {
                $access_token = $this->access_token;
            }

            if (is_object($access_token)) {
                $created = $access_token->created;
                $expires_in = $access_token->expires_in;
            } elseif (is_array($access_token)) {
                $created = $access_token['created'];
                $expires_in = $access_token['expires_in'];
            }

            $expires_in += $created;

            return JHtml::_('date', $expires_in, JText::_('COM_AUTOTWEET_DATE_FORMAT'));
        }

        return null;
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

        $result = [false, 'Blogger Unknown', null];

        try {
            $originalUrl = $data->org_url;
            $content = $this->renderPost($this->channel->id, 'pro.channels.blogger-post', $message, $data);

            $blogid = $this->get('blogid');
            $blog = new XTS_Google_Service_Blogger_PostBlog();
            $blog->setId($blogid);

            $googlePost = new XTS_Google_Service_Blogger_Post();
            $googlePost->setKind('blogger#post');
            $googlePost->setBlog($blog);
            $googlePost->setTitle($data->title);
            $googlePost->setContent($content);
            $googlePost->setUrl($originalUrl);

            $googlePost = $this->blogger->posts->insert($blogid, $googlePost);

            $msg = 'Post id: '.$googlePost->id.' - '.$googlePost->url;
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
     * getCallbackUrl.
     *
     * @param int    $channelId Param
     * @param string $callback  Param
     *
     * @return string
     */
    public static function getCallbackUrl($channelId, $callback = 'callback')
    {
        return \Joomla\CMS\Uri\Uri::base().'index.php?option=com_autotweet';
    }

    /**
     * Internal service functions.
     *
     * @return object
     */
    protected function getApiInstance()
    {
        if (!$this->bloggerClient) {
            $this->bloggerClient = new XTS_Google_Client();

            $sitename = \Joomla\CMS\Factory::getConfig()->get('sitename');
            $this->bloggerClient->setApplicationName($sitename);

            $this->bloggerClient->setAccessType('offline');
            $this->bloggerClient->setApprovalPrompt('force');
            $this->bloggerClient->setClientId($this->client_id);
            $this->bloggerClient->setClientSecret($this->client_secret);
            $this->bloggerClient->setDeveloperKey($this->developer_key);
            $this->bloggerClient->addScope(XTS_Google_Service_Blogger::BLOGGER);

            if ((isset($this->channel->id)) && ($this->channel->id)) {
                $url = $this->getCallbackUrl($this->channel->id);
                $this->bloggerClient->setRedirectUri($url);
            }

            $accessToken = $this->getAccessToken();

            if (!empty($accessToken)) {
                if (is_object($accessToken)) {
                    $accessToken = (array) $accessToken;
                }

                $this->bloggerClient->setAccessToken($accessToken);
            }

            $this->blogger = new XTS_Google_Service_Blogger($this->bloggerClient);
        }

        return $this->bloggerClient;
    }

    /**
     * _refreshToken().
     */
    private function _refreshToken()
    {
        $refresh_token = $this->bloggerClient->getRefreshToken();

        $this->access_token = $this->bloggerClient->refreshToken($refresh_token);
        $this->access_token['refresh_token'] = $refresh_token;

        return $this->access_token;
    }
}
