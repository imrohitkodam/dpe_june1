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
 * TumblrChannelHelper.
 *
 * @since       1.0
 */
class TumblrChannelHelper extends ChannelHelper
{
    protected $tumblrClient;

    protected $consumer_key;

    protected $consumer_secret;

    protected $access_token;

    protected $access_secret;

    protected $is_auth;

    protected $me;

    /**
     * ChannelHelper.
     *
     * @param object     $channel         params
     * @param mixed|null $consumer_key
     * @param mixed|null $consumer_secret
     * @param mixed|null $access_token
     * @param mixed|null $access_secret
     */
    public function __construct($channel = null, $consumer_key = null, $consumer_secret = null, $access_token = null, $access_secret = null)
    {
        if ($channel) {
            parent::__construct($channel);

            $this->consumer_key = $this->get('consumer_key');
            $this->consumer_secret = $this->get('consumer_secret');
            $this->access_token = $this->get('access_token');
            $this->access_secret = $this->get('access_secret');
        }

        if ($consumer_key) {
            $this->consumer_key = $consumer_key;
        }

        if ($consumer_secret) {
            $this->consumer_secret = $consumer_secret;
        }

        if ($access_token) {
            $this->access_token = $access_token;
        }

        if ($access_secret) {
            $this->access_secret = $access_secret;
        }
    }

    /**
     * login.
     *
     * @return object
     */
    public function login()
    {
        if (!$this->tumblrClient) {
            $this->tumblrClient = new \XTS_BUILD\Tumblr\API\Client(
                $this->consumer_key,
                $this->consumer_secret,
                $this->access_token,
                $this->access_secret
            );
        }

        return $this->tumblrClient;
    }

    /**
     * getApi.
     *
     * @return object
     */
    public function getApi()
    {
        return $this->tumblr;
    }

    /**
     * verify.
     *
     * @return bool
     */
    public function verify()
    {
        $connection = $this->login();

        if (empty($this->consumer_key)) {
            return false;
        }

        if (empty($this->consumer_secret)) {
            return false;
        }

        if (empty($this->access_token)) {
            return false;
        }

        if (empty($this->access_secret)) {
            return false;
        }

        try {
            $response = $connection->getUserInfo()->user;

            $message = [
                'status' => true,
                'error_message' => null,
                'user' => $response,
                'url' => $this->getSocialUrl($response),
            ];
        } catch (Exception $e) {
            $message = [
                'status' => false,
                'error_message' => $e->getMessage(),
            ];
        }

        return $message;
    }

    public function isAuth()
    {
        $results = $this->verify();

        return $results['status'];
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

        $result = [false, 'Tumblr Unknown', null];

        try {
            $mediaMode = $this->getMediaMode();

            // Test / Photo / Link
            $type = $this->channel->params->get('posttype', 'text');
            $basehostname = $posttype = $this->channel->params->get('basehostname');

            if (('message' === $mediaMode) && ('photo' === $type)) {
                return [
                    false,
                    JText::_('COM_AUTOTWEET_CHANNEL_TUMBLR_NOT_MEDIA_ERR'),
                ];
            }

            $imageUrl = null;

            if ('message' !== $mediaMode) {
                $imageUrl = $data->image_url;
            }

            if ((empty($imageUrl)) && ('photo' === $type)) {
                return [
                    false,
                    JText::_('COM_AUTOTWEET_CHANNEL_TUMBLR_NOT_MEDIA_ERR'),
                ];
            }

            $this->getApiInstance();

            $parameters = [];
            $parameters['type'] = $type;

            switch ($type) {
                case 'photo':
                    $parameters['caption'] = $message;
                    $parameters['link'] = $data->org_url;
                    $parameters['source'] = $imageUrl;

                    break;
                case 'link':
                    $parameters['title'] = $data->title;
                    $parameters['url'] = $data->org_url;
                    $parameters['description'] = $message;

                    break;
                default:
                    $parameters['type'] = 'text';
                    $parameters['title'] = $data->title;
                    $parameters['body'] = $this->renderPost($this->channel->id, 'pro.channels.tumblr-post', $message, $data);
            }

            $post = $this->tumblrClient->createPost($basehostname, $parameters);
            $result = [
                true,
                $post->state.' - '.'https://'.$basehostname.'/'.$post->id,
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
        if ((isset($user->blogs)) && (count($user->blogs) > 0)) {
            return $user->blogs[0]->url;
        }

        return 'https://www.tumblr.com';
    }

    /**
     * Internal service functions.
     *
     * @return object
     */
    protected function getApiInstance()
    {
        if (!$this->tumblrClient) {
            $this->tumblrClient = new \XTS_BUILD\Tumblr\API\Client($this->consumer_key, $this->consumer_secret);
            $this->tumblrClient->setToken($this->access_token, $this->access_secret);
        }

        return $this->tumblrClient;
    }
}
