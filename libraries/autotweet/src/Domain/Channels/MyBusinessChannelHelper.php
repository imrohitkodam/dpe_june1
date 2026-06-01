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
 * MyBusinessChannelHelper.
 *
 * @since       1.0
 */
class MyBusinessChannelHelper extends ChannelHelper
{
    protected $myBusinessClient;

    protected $myBusiness;

    protected $client_id;

    protected $client_secret;

    protected $access_token;

    protected $is_auth;

    protected $accounts;

    protected $me;

    protected $locations;

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

            $access_token = $this->channel->params->get('access_token');
            $this->setAccessToken($access_token);
        }
    }

    /**
     * setAccessToken.
     *
     * @param string $access_token Param
     *
     * @return void
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
            $isExpired = $this->myBusinessClient->isAccessTokenExpired();

            if (!$isExpired) {
                $user = $this->getUser();

                return true;
            }

            $this->_refreshToken();

            // Second try, and the last
            $isExpired = $this->myBusinessClient->isAccessTokenExpired();

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

        $this->access_token = $this->myBusinessClient->authenticate($code);

        $ch = XTF0FTable::getAnInstance('Channel', 'AutoTweetTable');

        if ((is_array($this->access_token)) && (isset($this->access_token['error']))) {
            $logger = AutotweetLogger::getInstance();
            $logger->log(\Joomla\CMS\Log\Log::ERROR, $this->access_token['error'].' - '.$this->access_token['error_description']);

            // Just in case, it will be shown someday
            \Joomla\CMS\Factory::getApplication()->enqueueMessage(JText::_('COM_AUTOTWEET_ERR_UNABLE_RETRIEVE'), 'error');

            // Invalidating access_token
            $ch->setToken($this->channel->id, 'access_token', '');

            return false;
        }

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

        return $this->myBusinessClient->createAuthUrl();
    }

    /**
     * getUser.
     *
     * @return object
     */
    public function getUser()
    {
        if (!$this->me) {
            $listAccountsResponse = $this->myBusiness->accounts->listAccounts();
            $this->accounts = $listAccountsResponse->getAccounts();

            if (empty($this->accounts)) {
                return false;
            }

            $this->me = $this->accounts[0];

            foreach ($this->accounts as $account) {
                if ('PERSONAL' === $account->type) {
                    $this->me = $account;

                    break;
                }
            }
        }

        return $this->me;
    }

    /**
     * getUser.
     *
     * @return object
     */
    public function getLocations()
    {
        if (!$this->locations) {
            if (empty($this->accounts)) {
                return null;
            }

            $this->locations = [];

            foreach ($this->accounts as $account) {
                $accountsLocationsResponse = $this->myBusiness->accounts_locations->listAccountsLocations($account->name);

                foreach ($accountsLocationsResponse as $location) {
                    $state = $location->getLocationState();

                    if ($state->isLocalPostApiDisabled) {
                        continue;
                    }

                    $this->locations[] = $location;
                }
            }
        }

        return $this->locations;
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

        $result = [false, 'MyBusiness Unknown', null];

        try {
            $imageUrl = $data->image_url;
            $originalUrl = $data->org_url;

            if (empty($imageUrl) || empty($originalUrl)) {
                return [
                    false,
                    'The post must have a Link and an Image.',
                ];
            }

            $newPost = new XTS_Google_Service_MyBusiness_LocalPost();
            $newPost->setSummary($message);
            $newPost->setTopicType('STANDARD');

            $languageCode = EParameter::getComponentParam('com_languages', 'site', 'en-GB');
            $newPost->setLanguageCode($languageCode);

            $calltoaction = new XTS_Google_Service_MyBusiness_CallToAction();
            $calltoaction->setActionType('LEARN_MORE');
            $calltoaction->setUrl($originalUrl);
            $newPost->setCallToAction($calltoaction);

            if (($this->isMediaModePostWithImage()) && (!empty($imageUrl))) {
                $media = new XTS_Google_Service_MyBusiness_MediaItem();
                $media->setMediaFormat('PHOTO');
                $media->setSourceUrl($imageUrl);
                $newPost->setMedia($media);
            }

            $locationName = $this->get('location_name');
            $listPostsResponse = $this->myBusiness->accounts_locations_localPosts->create($locationName, $newPost);

            $msg = 'Post id: '.$listPostsResponse->name.' - '.$listPostsResponse->searchUrl;
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
     * Internal service functions.
     *
     * @return object
     */
    protected function getApiInstance()
    {
        if (!$this->myBusinessClient) {
            $this->myBusinessClient = new XTS_Google_Client();

            $sitename = \Joomla\CMS\Factory::getConfig()->get('sitename');
            $this->myBusinessClient->setApplicationName($sitename);

            $this->myBusinessClient->setAccessType('offline');
            $this->myBusinessClient->setApprovalPrompt('force');
            $this->myBusinessClient->setClientId($this->client_id);
            $this->myBusinessClient->setClientSecret($this->client_secret);
            $this->myBusinessClient->addScope('https://www.googleapis.com/auth/plus.business.manage');

            if ((isset($this->channel->id)) && ($this->channel->id)) {
                $url = $this->getCallbackUrl($this->channel->id);
                $this->myBusinessClient->setRedirectUri($url);
            }

            $accessToken = $this->getAccessToken();

            if (!empty($accessToken)) {
                if (is_object($accessToken)) {
                    $accessToken = (array) $accessToken;
                }

                $this->myBusinessClient->setAccessToken($accessToken);
            }

            $this->myBusinessClient->setApiFormatV2(2);

            $this->myBusiness = new XTS_Google_Service_MyBusiness($this->myBusinessClient);
        }

        return $this->myBusinessClient;
    }

    /**
     * _refreshToken().
     *
     * @return void
     */
    private function _refreshToken()
    {
        $refresh_token = $this->myBusinessClient->getRefreshToken();

        $this->access_token = $this->myBusinessClient->refreshToken($refresh_token);
        $this->access_token['refresh_token'] = $refresh_token;

        return $this->access_token;
    }

    /**
     * getCallbackUrl.
     *
     * @param int    $channelId Param
     * @param string $callback  Param
     *
     * @return string
     */
    private function getCallbackUrl($channelId, $callback = 'callback')
    {
        return \Joomla\CMS\Uri\Uri::base().'index.php?option=com_autotweet';
    }
}
