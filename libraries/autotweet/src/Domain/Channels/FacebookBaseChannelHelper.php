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
 * FacebookBaseChannelHelper class.
 *
 * @since       1.0
 */
abstract class FacebookBaseChannelHelper extends ChannelHelper
{
    public const MAX_CHARS_NAME = 255;

    protected $facebook;

    protected $metadata;

    protected $lastResponse;

    protected $isApi11OrSuperior = false;

    /**
     * getApiInstance.
     *
     * @return object
     */
    protected function getApiInstance()
    {
        if (!$this->facebook) {
            $this->facebook = new \XTS_BUILD\Facebook\Facebook(
                [
                    'app_id' => $this->get('app_id'),
                    'app_secret' => $this->get('secret'),
                    'default_graph_version' => 'v8.0',
                    'default_access_token' => $this->get('access_token'),
                ]
            );
        }

        return $this->facebook;
    }

    /**
     * sendFacebookMessage.
     *
     * @param string $composedMsg Params
     * @param string $title       Params
     * @param string $message     Params
     * @param string $url         Params
     * @param string $articleUrl  Params
     * @param string $imageUrl    Params
     * @param string $mediaMode  Params
     * @param object &$post       Params
     *
     * @return bool
     */
    protected function sendFacebookOG($composedMsg, $title, $message, $url, $articleUrl, $imageUrl, $mediaMode, &$post)
    {
        $logger = AutotweetLogger::getInstance();
        $logger->log(\Joomla\CMS\Log\Log::INFO, 'sendFacebookOG', $composedMsg);

        // Simulated
        if (!(bool) $this->channel->params->get('use_own_api')) {
            return $this->testMessage();
        }

        $fbId = $this->getFbChannelId();
        $accessToken = $this->get('fbchannel_access_token');
        $accessToken = $this->matchToken($accessToken, $fbId);

        $explicitlyShared = (bool) $this->channel->params->get('og_explicitly_shared', true);

        $arguments = [
            'access_token' => $accessToken,
            'article' => $articleUrl,
            'message' => $message,
            'fb:explicitly_shared' => $explicitlyShared,
        ];

        if (!empty($imageUrl)) {
            $userGenerated = (bool) $this->channel->params->get('og_user_generated', true);
            $arguments['image[0][url]'] = $imageUrl;
            $arguments['image[0][user_generated]'] = $userGenerated;
        }

        try {
            $post = $this->callApiPost("/{$fbId}/news.publishes", $arguments, $accessToken);

            $msg = 'Facebook id: '.$post['id'];
            $result = [
                true,
                $msg,
            ];
        } catch (Exception $e) {
            $result = [
                false,
                $e->getCode().' - '.$e->getMessage(),
            ];
        }

        return $result;
    }

    /**
     * isUserProfile.
     *
     * @return bool
     */
    protected function isUserProfile()
    {
        // try
        // {
        // 	$user = $this->getMetadata();

        // 	if (isset($user['metadata']['type']))
        // 	{
        // 		return ($user['metadata']['type'] == 'user');
        // 	}

        // 	return ( ($user['id'] == $fbId) && (array_key_exists('link', $user)) && (strpos($user['link'], 'www.facebook.com/app_scoped_user_id') !== false) );
        // }
        // catch (Exception $e)
        // {
        // 	$code = $e->getCode();
        // 	$msg = $code . ' - ' . $e->getMessage();

        // 	$logger = AutotweetLogger::getInstance();
        // 	$logger->log(\Joomla\CMS\Log\Log::ERROR, 'isUserProfile: ' . $msg);
        // }

        return false;
    }

    /**
     * getMetadata.
     *
     * @return array
     */
    protected function getMetadata()
    {
        if ($this->metadata) {
            return $this->metadata;
        }

        $fbId = $this->getFbChannelId();
        $accessToken = $this->get('fbchannel_access_token');

        $this->metadata = $this->callApiGet('/'.$fbId.'?metadata=1', $accessToken);

        return $this->metadata;
    }

    /**
     * getFbChannelId.
     *
     * @return string
     */
    protected function getFbChannelId()
    {
        $fbchannel_id = $this->get('fbchannel_id');

        if (!$fbchannel_id) {
            return 'me';
        }

        return $fbchannel_id;
    }

    /**
     * callApiGet.
     *
     * @param string $query       Params
     * @param string $accessToken Params
     *
     * @return array
     */
    protected function callApiGet($query, $accessToken = null)
    {
        $result = [];

        if (empty($accessToken)) {
            $accessToken = $this->get('fbchannel_access_token');
        }

        if (empty($accessToken)) {
            $accessToken = $this->get('access_token');
        }

        try {
            $this->getApiInstance();

            $response = $this->facebook->get(
                $query,
                $accessToken
            );
            $graphNode = $response->getGraphNode();

            $this->lastResponse = $response;
            $headers = $response->getHeaders();
            $facebookApiVersion = $headers['facebook-api-version'];
            $this->isApi11OrSuperior = version_compare($facebookApiVersion, 'v2.11', '>=');

            return $graphNode;
        } catch (XTS_BUILD\Facebook\Exceptions\FacebookResponseException $e) {
            throw new Exception('Graph error: '.$e->getMessage(), $e->getCode());
        } catch (XTS_BUILD\Facebook\Exceptions\FacebookSDKException $e) {
            throw new Exception('Facebook SDK error: '.$e->getMessage(), $e->getCode());
        }

        return $result;
    }

    /**
     * callApiPost.
     *
     * @param string $query       Params
     * @param array  $data        Params
     * @param string $accessToken Params
     *
     * @return array
     */
    protected function callApiPost($query, $data, $accessToken = null)
    {
        $result = [];

        if (empty($accessToken)) {
            $accessToken = $this->get('fbchannel_access_token');
        }

        if (empty($accessToken)) {
            $accessToken = $this->get('access_token');
        }

        try {
            $this->getApiInstance();

            $response = $this->facebook->post(
                $query,
                $data,
                $accessToken
            );
            $graphNode = $response->getGraphNode();

            return $graphNode;
        } catch (XTS_BUILD\Facebook\Exceptions\FacebookResponseException $e) {
            throw new Exception('Graph error: '.$e->getMessage(), $e->getCode());
        } catch (XTS_BUILD\Facebook\Exceptions\FacebookSDKException $e) {
            throw new Exception('Facebook SDK error: '.$e->getMessage(), $e->getCode());
        }

        return $result;
    }

    /**
     * testMessage.
     *
     * @return array
     */
    protected function testMessage()
    {
        try {
            $this->callApiGet('/me');

            $result = [
                true,
                JText::_('COM_AUTOTWEET_VIEW_SIMULATED_OK'),
            ];

            return $result;
        } catch (Exception $e) {
            $code = $e->getCode();
            $msg = $code.' - '.$e->getMessage();

            $result = [
                false,
                $msg,
            ];

            return $result;
        }
    }

    /**
     * matchToken.
     *
     * @param string $accessToken Params
     * @param string $fbChannelId Params
     *
     * @return string
     */
    protected function matchToken($accessToken, $fbChannelId)
    {
        // Just to be sure - Fatal error: Call to a member function getOAuth2Client()
        $this->getApiInstance();

        $oAuth2Client = $this->facebook->getOAuth2Client();
        $tokenMetadata = $oAuth2Client->debugToken($accessToken);

        // Ok, it matches
        if ($tokenMetadata->getProfileId() === $fbChannelId) {
            return $accessToken;
        }

        // Let's generate a new channel token
        $metadata = $this->getMetadata();

        // Not a page, so publish with any token we have
        if ((isset($metadata['metadata']['type']))
            && ('page' !== $metadata['metadata']['type'])) {
            return $accessToken;
        }

        // Hmmm, it's a page and token does not match ... let's load a new one
        $accessToken = $this->get('access_token');

        $response = $this->facebook->get(
            '/'.$fbChannelId.'?fields=id,access_token',
            $accessToken
        );
        $item = $response->getGraphNode();
        $graphNode = $item->asArray();

        if ($graphNode['id'] === $fbChannelId) {
            return $graphNode['access_token'];
        }

        // I give up
        return $accessToken;
    }
}
