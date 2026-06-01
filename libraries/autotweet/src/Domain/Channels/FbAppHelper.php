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
 * FbAppHelper class.
 *
 * @since       1.0
 */
class FbAppHelper
{
    public const TESTING_APP_ID = 'TXktQXBwLUlE';

    public const PAGE_LIMIT = 1024;

    protected $appId;

    protected $secret;

    protected $accessToken;

    private $facebook;

    /**
     * FbAppHelper.
     *
     * @param string $app_id       Params
     * @param string $secret       Params
     * @param string $access_token Params
     */
    public function __construct($app_id, $secret, $access_token)
    {
        $this->appId = $app_id;
        $this->secret = $secret;
        $this->accessToken = $access_token;
    }

    /**
     * login.
     *
     * @param bool $force Params
     *
     * @return object
     */
    public function login($force = false)
    {
        if ((!$this->facebook) || ($force)) {
            $this->facebook = new \XTS_BUILD\Facebook\Facebook(
                [
                    'app_id' => $this->appId,
                    'app_secret' => $this->secret,
                    'default_graph_version' => 'v8.0',
                    'default_access_token' => $this->accessToken,
                ]
            );
        }

        return $this->facebook;
    }

    /**
     * verify.
     *
     * @return bool
     */
    public function verify()
    {
        $result = null;

        if (empty($this->accessToken)) {
            $result = [
                false,
                'Facebook Token not entered.',
            ];

            return $result;
        }

        try {
            $response = $this->facebook->get('/me', $this->accessToken);
            $me = $response->getGraphUser();

            $result = [
                true,
                JText::_('COM_AUTOTWEET_OK'),
            ];
        } catch (XTS_BUILD\Facebook\Exceptions\FacebookResponseException $e) {
            $result = [
                false,
                'Graph returned an error: '.$e->getMessage(),
            ];
        } catch (XTS_BUILD\Facebook\Exceptions\FacebookSDKException $e) {
            $result = [
                false,
                'Facebook SDK returned an error: '.$e->getMessage(),
            ];
        } catch (Exception $e) {
            $result = [
                false,
                'Error: '.$e->getMessage(),
            ];
        }

        return $result;
    }

    /**
     * getUser.
     *
     * @param string $userId params
     *
     * @return object
     */
    public function getUser($userId = null)
    {
        if ((empty($userId)) || (!is_numeric($userId))) {
            $user_id = 'me';
        }

        $response = $this->facebook->get(
            '/'.$user_id.'?fields=id,name,link',
            $this->accessToken
        );

        // $logger = AutotweetLogger::getInstance();
        // $logger->log(\Joomla\CMS\Log\Log::INFO, "FB getUser: " . print_r($response, true));

        $user = $response->getGraphUser();

        return $user;
    }

    /**
     * getChannels.
     *
     * @return array
     */
    public function getChannels()
    {
        $pages = $this->_getPagesAsChannel();
        $groups = $this->_getGroupsAsChannel();

        return array_merge($pages, $groups);
    }

    /**
     * getPageChannels.
     *
     * @return array
     */
    public function getPageChannels()
    {
        $pages = $this->_getPagesAsChannel();

        return $pages;
    }

    /**
     * getAlbums.
     *
     * @param string $channelId Params
     *
     * @return array
     */
    public function getAlbums($channelId)
    {
        $result = [];

        try {
            $response = $this->facebook->get(
                "/{$channelId}/albums?fields=id,name,can_upload&limit=".self::PAGE_LIMIT,
                $this->accessToken
            );
            $items = $response->getGraphEdge();

            $result[] = [
                'type' => 'Album',
                'id' => 0,
                'name' => ' - Default - ',
            ];

            foreach ($items as $item) {
                $graphNode = $item->asArray();

                if ($graphNode['can_upload']) {
                    $a = [
                        'type' => 'Album',
                        'id' => $graphNode['id'],
                        'name' => $graphNode['name'],
                    ];
                    $result[] = $a;
                }
            }
        } catch (XTS_BUILD\Facebook\Exceptions\FacebookResponseException $e) {
            $result[] = [
                'type' => 'Graph error: '.$e->getMessage(),
                'id' => '0',
                'name' => 'Album '.$e->getMessage(),
                'token' => '',
            ];
        } catch (XTS_BUILD\Facebook\Exceptions\FacebookSDKException $e) {
            $result[] = [
                'type' => 'Facebook SDK error: '.$e->getMessage(),
                'id' => '0',
                'name' => 'Album '.$e->getMessage(),
                'token' => '',
            ];
        } catch (Exception $e) {
            $result[] = [
                'type' => 'Error: '.$e->getMessage(),
                'id' => '0',
                'name' => 'Album '.$e->getMessage(),
                'token' => '',
            ];
        }

        return $result;
    }

    /**
     * getDebugToken.
     *
     * @param string $fbAccessToken Params
     *
     * @return array
     */
    public function getDebugToken($fbAccessToken = null)
    {
        if (self::TESTING_APP_ID === $this->appId) {
            $response = [];
            $response['issued_at'] = '---';
            $response['expires_at'] = '---';

            return $response;
        }

        if (!$fbAccessToken) {
            $fbAccessToken = $this->accessToken;
        }

        try {
            $oAuth2Client = $this->facebook->getOAuth2Client();
            $tokenMetadata = $oAuth2Client->debugToken($this->accessToken);

            // $logger = AutotweetLogger::getInstance();
            // $logger->log(\Joomla\CMS\Log\Log::INFO, "FB getDebugToken: " . print_r($tokenMetadata, true));

            $tokenMetadata->validateAppId($this->appId);
            $tokenMetadata->validateExpiration();

            $issuedAt = $tokenMetadata->getIssuedAt();
            $timestamp = null;

            if ($issuedAt) {
                $timestamp = $issuedAt->getTimestamp();
            }

            $issuedAt = new JDate($timestamp);
            $response['issued_at'] = JHtml::_(
                'date',
                $issuedAt,
                JText::_('COM_AUTOTWEET_DATE_FORMAT')
            );

            $expiresAt = $tokenMetadata->getExpiresAt();

            if ($expiresAt) {
                $expiresAt = new JDate($tokenMetadata->getExpiresAt()->getTimestamp());

                $response['expires_at'] = JHtml::_(
                    $expiresAt,
                    JText::_('COM_AUTOTWEET_DATE_FORMAT')
                );
            } else {
                $response['expires_at'] = JText::_('COM_AUTOTWEET_VIEW_FBWACCOUNT_NEVER');
            }
        } catch (Exception $e) {
            $response = [];
            $response['issued_at'] = '------';
            $response['expires_at'] = '------';
        }

        return $response;
    }

    /**
     * getExtendedAccessToken.
     *
     * @return string
     */
    public function getExtendedAccessToken()
    {
        try {
            $oAuth2Client = $this->facebook->getOAuth2Client();
            $tokenMetadata = $oAuth2Client->debugToken($this->accessToken);
            $tokenMetadata->validateAppId($this->appId);
            $tokenMetadata->validateExpiration();
            $longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($this->accessToken);

            return $longLivedAccessToken->getValue();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * _getPagesAsChannel.
     *
     * @return array
     */
    private function _getPagesAsChannel()
    {
        $result = [];

        try {
            $response = $this->facebook->get(
                '/me/accounts?fields=id,name,link,access_token&limit='.self::PAGE_LIMIT,
                $this->accessToken
            );
            $items = $response->getGraphEdge();

            foreach ($items as $item) {
                $graphNode = $item->asArray();
                $result[] = [
                    'type' => 'Page',
                    'id' => $graphNode['id'],
                    'name' => $graphNode['name'],
                    'url' => $graphNode['link'],
                    'access_token' => $graphNode['access_token'],
                ];
            }
        } catch (XTS_BUILD\Facebook\Exceptions\FacebookResponseException $e) {
            $result[] = [
                'type' => 'Graph error: '.$e->getMessage(),
                'id' => '0',
                'name' => 'Pages '.$e->getMessage(),
                'token' => '',
            ];
        } catch (XTS_BUILD\Facebook\Exceptions\FacebookSDKException $e) {
            $result[] = [
                'type' => 'Facebook SDK error: '.$e->getMessage(),
                'id' => '0',
                'name' => 'Pages '.$e->getMessage(),
                'token' => '',
            ];
        } catch (Exception $e) {
            $result[] = [
                'type' => 'Error: '.$e->getMessage(),
                'id' => '0',
                'name' => 'Pages '.$e->getMessage(),
                'token' => '',
            ];
        }

        return $result;
    }

    /**
     * _getGroupsAsChannel.
     *
     * @return array
     */
    private function _getGroupsAsChannel()
    {
        $result = [];

        try {
            $response = $this->facebook->get(
                '/me/groups?fields=id,name&limit='.self::PAGE_LIMIT,
                $this->accessToken
            );
            $items = $response->getGraphEdge();

            foreach ($items as $item) {
                $graphNode = $item->asArray();
                $url = 'https://www.facebook.com/'.$graphNode['id'];

                $result[] = [
                    'type' => 'Group',
                    'id' => $graphNode['id'],
                    'name' => $graphNode['name'],
                    'url' => $url,
                    'access_token' => $this->accessToken,
                ];
            }
        } catch (XTS_BUILD\Facebook\Exceptions\FacebookResponseException $e) {
            $result[] = [
                'type' => 'Graph error: '.$e->getMessage(),
                'id' => '0',
                'name' => 'Group '.$e->getMessage(),
                'token' => '',
            ];
        } catch (XTS_BUILD\Facebook\Exceptions\FacebookSDKException $e) {
            $result[] = [
                'type' => 'Facebook SDK error: '.$e->getMessage(),
                'id' => '0',
                'name' => 'Group '.$e->getMessage(),
                'token' => '',
            ];
        } catch (Exception $e) {
            $result[] = [
                'type' => 'Error: '.$e->getMessage(),
                'id' => '0',
                'name' => 'Group '.$e->getMessage(),
                'token' => '',
            ];
        }

        return $result;
    }

    /**
     * _getUserAsChannel.
     *
     * @return array
     */
    private function _getUserAsChannel()
    {
        $result = [];

        try {
            $user = $this->getUser();

            if ($user) {
                $result[] = [
                    'type' => 'User',
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'url' => $user->getLink(),
                    'access_token' => $this->accessToken,
                ];
            }
        } catch (XTS_BUILD\Facebook\Exceptions\FacebookResponseException $e) {
            $result[] = [
                'type' => 'Graph error: '.$e->getMessage(),
                'id' => '0',
                'name' => 'User '.$e->getMessage(),
                'token' => '',
            ];
        } catch (XTS_BUILD\Facebook\Exceptions\FacebookSDKException $e) {
            $result[] = [
                'type' => 'Facebook SDK error: '.$e->getMessage(),
                'id' => '0',
                'name' => 'User '.$e->getMessage(),
                'token' => '',
            ];
        } catch (Exception $e) {
            $result[] = [
                'type' => 'Error: '.$e->getMessage(),
                'id' => '0',
                'name' => 'User '.$e->getMessage(),
                'token' => '',
            ];
        }

        return $result;
    }
}
