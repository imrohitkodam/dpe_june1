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
 * LiOAuth2ChannelHelper.
 *
 * @since       1.0
 */
class LiOAuth2ChannelHelper extends ChannelHelper
{
    // https://docs.microsoft.com/en-us/linkedin/marketing/integrations/community-management/shares/share-api

    public const MAX_CHARS_MESSAGE = 1300;

    public const MAX_CHARS_TITLE = 400;

    public const MAX_CHARS_DESC = 256;

    public const API1_GENERAL_PERMS = [
        'r_basicprofile',
        'rw_company_admin',
        'w_share',
    ];

    public const API2_PROFILE_PERMS = [
        'r_liteprofile',
        'w_member_social',
    ];

    public const API2_COMPANY_PERMS = [
        'r_liteprofile',
        'r_basicprofile',
        'r_organization_social',
        'w_organization_social',
        'rw_organization_admin',
    ];

    public const API_v1 = '1';

    public const API_v2 = '2';

    protected $lioauth2Client;

    protected $lioauth2Callback;

    protected $consumer_key;

    protected $consumer_secret;

    protected $access_token;

    protected $access_secret;

    protected $expires_in;

    protected $is_auth;

    protected $me;

    protected $apiVersion;

    /**
     * ChannelHelper.
     *
     * @param object $channel params
     */
    public function __construct($channel)
    {
        parent::__construct($channel);

        if ($channel->id) {
            $this->consumer_key = $this->channel->params->get('consumer_key');
            $this->consumer_secret = $this->channel->params->get('consumer_secret');
            $this->access_token = $this->channel->params->get('access_token');
            $this->access_secret = $this->channel->params->get('access_secret');

            $this->apiVersion = $this->channel->params->get('li_api_version', self::API_v1);
        }
    }

    /**
     * getUser().
     *
     * @return mixed
     */
    public function getUser()
    {
        if (empty($this->access_token)) {
            $this->access_token = null;

            return false;
        }

        $response = $this->getMe();

        if (isset($response['id'])) {
            return $this->prepareUserResult($response);
        }

        return $this->processResponse($response);
    }

    /**
     * isAuth().
     *
     * @return mixed
     */
    public function isAuth()
    {
        if (empty($this->access_token)) {
            $this->access_token = null;

            return false;
        }

        $result = false;

        try {
            $result = $this->getUser();
        } catch (Exception $e) {
            $logger = AutotweetLogger::getInstance();
            $logger->log(\Joomla\CMS\Log\Log::ERROR, $e->getMessage());

            // Just in case, it is shown someday
            \Joomla\CMS\Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

            // Invalidating access_token
            $this->saveAccessToken($this->channel->id);
        }

        return $result;
    }

    /**
     * getAuthorizationUrl.
     *
     * @return string
     */
    public function getAuthorizationUrl()
    {
        return $this->getAuthorizationUrlInternal(self::API2_PROFILE_PERMS);
    }

    /**
     * authenticate.
     *
     * @param string $code  Param
     * @param string $state Param
     *
     * @return bool
     */
    public function authenticate($code, $state)
    {
        $access_token = $this->getAccessToken($code, $state);

        if ($access_token) {
            $now = null;
            $expiresAt = $access_token->getExpiresAt();

            if ($expiresAt) {
                $expiresAt = $expiresAt->getTimestamp();
                $now = \Joomla\CMS\Factory::getDate($expiresAt)->toSql();
            }

            $this->saveAccessToken(
                $this->channel->id,
                $access_token->getToken(),
                $expiresAt,
                '',
                $now
            );
        } else {
            $msg = 'Unable to retrieve access token. ('.$code.','.$state.')';
            \Joomla\CMS\Factory::getApplication()->enqueueMessage($msg, 'error');

            $logger = AutotweetLogger::getInstance();
            $logger->log(\Joomla\CMS\Log\Log::INFO, 'LiOAuth2ChannelHelper '.$msg);

            // Invalidating access_token
            $this->saveAccessToken($this->channel->id);
        }
    }

    /**
     * getAccessToken.
     *
     * @param string $code  Param
     * @param string $state Param
     *
     * @return bool
     */
    public function getAccessToken($code, $state)
    {
        $redirectUri = $this->getCallbackUrl($this->channel->id);
        $this->getApiInstance($redirectUri);
        $access_token = $this->lioauth2Client->getAccessToken();

        if (($access_token) && ($access_token->hasToken())) {
            return $access_token;
        }

        $this->access_token_error = null;
        $this->access_token_error_description = null;

        if (($access_token) && (isset($access_token->error)) && (isset($access_token->error_description))) {
            $this->access_token_error = $access_token->error;
            $this->access_token_error_description = $access_token->error_description;
        }

        return null;
    }

    /**
     * saveAccessToken.
     *
     * @param int    $id           Param
     * @param string $token        Param
     * @param string $expires_in   Param
     * @param string $user_id      Param
     * @param string $expires_date Param
     *
     * @return bool
     */
    public function saveAccessToken($id, $token = '', $expires_in = '', $user_id = '', $expires_date = '')
    {
        $ch = XTF0FTable::getAnInstance('Channel', 'AutoTweetTable');

        $ch->setToken($id, 'access_token', $token);
        $ch->setToken($id, 'expires_in', $expires_in);
        $ch->setToken($id, 'user_id', $user_id);
        $ch->setToken($id, 'expires_date', $expires_date);
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
        return $user->publicProfileUrl;
    }

    /**
     * sendMessage.
     *
     * @param string $message Params
     * @param object $data    Params
     *
     * @return bool
     */
    public function sendMessage($message, $data)
    {
        $result = [
            false,
            'Unknown error',
        ];

        $isAuth = $this->isAuth();

        if (!$isAuth) {
            return [
                false,
                'LinkedIn is not authorized.',
            ];
        }

        $author = $isAuth['user']->id;

        return $this->sendMessageAPIv2($author, $message, $data);
    }

    /**
     * Internal service functions.
     *
     * @param string $callbackUrl Param
     *
     * @return object
     */
    protected function getApiInstance($callbackUrl = null)
    {
        if ((!$this->lioauth2Client) || ($this->lioauth2Callback !== $callbackUrl)) {
            $this->lioauth2Callback = $callbackUrl;

            $this->lioauth2Client = new \XTS_BUILD\Happyr\LinkedIn\LinkedIn(
                $this->consumer_key,
                $this->consumer_secret
            );

            if ($this->access_token) {
                $this->lioauth2Client->setAccessToken($this->access_token);
            }
        }

        return $this->lioauth2Client;
    }

    /**
     * getAuthorizationUrl.
     *
     * @param mixed $scope
     *
     * @return string
     */
    protected function getAuthorizationUrlInternal($scope)
    {
        if (empty($this->consumer_key)) {
            return null;
        }

        $session = \Joomla\CMS\Factory::getSession();
        $session->set('linkedin-authstate', 1);

        $redirectUri = $this->getCallbackUrl($this->channel->id);
        $this->getApiInstance($redirectUri);

        return $this->lioauth2Client->getLoginUrl(
            [
                'redirect_uri' => $redirectUri,
                'scope' => $scope,
            ]
        );
    }

    protected function sendMessageAPIv2($author, $message, $data)
    {
        $title = $data->title;
        $fulltext = $data->fulltext;
        $url = !empty($data->url) ? $data->url : $data->org_url;
        $imageUrl = $data->image_url;
        $result = null;
        $content = [];

        if (empty($url)) {
            return [
                false,
                'LinkedIn Posts must have an URL. ShareMedia.media (URN) must be present unless articles are being scraped.',
            ];
        }

        $postWithImage = true;

        if ($this->isMediaModeTextOnlyPost()) {
            $postWithImage = false;
        }

        $contentEntity = new StdClass();
        $contentEntity->entityLocation = $url;

        if (($postWithImage) && (!empty($imageUrl))) {
            $thumbnail = new StdClass();
            $thumbnail->resolvedUrl = $imageUrl;

            $contentEntity->thumbnails = [$thumbnail];
        }

        $content['content']['contentEntities'] = [$contentEntity];

        // Message
        $content['text'] = (object) ['text' => TextUtil::truncString($message, self::MAX_CHARS_MESSAGE)];

        if (!empty($title)) {
            $content['content']['title'] = TextUtil::truncString($title, self::MAX_CHARS_TITLE);
        }

        if (!empty($fulltext)) {
            $content['content']['description'] = TextUtil::truncString($fulltext, self::MAX_CHARS_DESC);
        }

        // $content['subject'] not shown

        $content['distribution'] = [
            'linkedInDistributionTarget' => (object) [
                'connectionsOnly' => false,
                'visibleToGuest' => true,
            ],
        ];

        $content['owner'] = $this->getUrn($author);

        try {
            $logger = AutotweetLogger::getInstance();
            $logger->log(\Joomla\CMS\Log\Log::INFO, 'LiOAuth2ChannelHelper sendMessage', $content);

            $api = $this->getLinkedInAPIv2();
            $options['body'] = json_encode($content);

            $response = $api->post('v2/shares', $options);

            if (isset($response['activity'])) {
                $result = [
                    true,
                    JText::_('COM_AUTOTWEET_OK').' - '.$response['activity'],
                ];

                return $result;
            }

            if (isset($response['message'])) {
                $result = [
                    false,
                    $response['message'].' - '.$response['status'],
                ];

                return $result;
            }
        } catch (Exception $e) {
            $result = [
                false,
                $e->getMessage(),
            ];
        }

        return $result;
    }

    protected function getValue($field)
    {
        $localized = $field['localized'];

        return array_shift($localized);
    }

    protected function getMe()
    {
        return $this->getMeAPIv2();
    }

    protected function getMeAPIv2()
    {
        $api = $this->getLinkedInAPIv2();

        return $api->get('/v2/me?fields=id,firstName,lastName');
    }

    protected function prepareUserResult($response)
    {
        return $this->prepareUserResultAPIv2($response);
    }

    protected function prepareUserResultAPIv2($response)
    {
        $url = 'https://www.linkedin.com/in/'.($response['vanityName'] ?? '#Not-Available-Lite-Profile');

        $user = new StdClass();
        $user->id = $response['id'];
        $user->url = $url;
        $user->publicProfileUrl = $url;

        return [
            'status' => true,
            'error_message' => 'Ok!',
            'user' => $user,
            'url' => $url,
        ];
    }

    protected function processResponse($response)
    {
        $errorMessage = $response['message'].' '.$response['status'];

        $logger = AutotweetLogger::getInstance();
        $logger->log(\Joomla\CMS\Log\Log::ERROR, $errorMessage);

        // Just in case, it is shown someday
        \Joomla\CMS\Factory::getApplication()->enqueueMessage($errorMessage, 'error');

        // Invalidating access_token
        $this->channel->setToken($this->channel->id, 'access_token', '');

        return [
            'status' => false,
            'error_message' => $errorMessage,
        ];
    }

    protected function getLinkedInAPIv2()
    {
        $api = $this->getApiInstance();

        $options = [];
        $options['headers']['X-RestLi-Protocol-Version'] = '2.0.0';
        $api->setFormat(null);

        return $api;
    }

    protected function getUrn($id)
    {
        return 'urn:li:person:'.$id;
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
        return \Joomla\CMS\Uri\Uri::base().'index.php?option=com_autotweet&_token='.\Joomla\CMS\Factory::getSession()->getFormToken();
    }
}
