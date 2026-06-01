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
 * ChannelHelper class.
 *
 * Base class for AutoTweet channels like Twitter of Facebook.
 * Works also as interface for usage.
 *
 * @since       1.0
 */
abstract class ChannelHelper
{
    protected $channel;

    protected $cached_max_chars;

    protected $has_tmp_file = false;

    /**
     * ChannelHelper.
     *
     * @param object &$ch Params
     */
    public function __construct(&$ch)
    {
        $channel = clone $ch;

        if ((property_exists($channel, 'params')) && (is_string($channel->params))) {
            $params = $channel->params;
            unset($channel->params);

            // Convert the params field to an array.
            $registry = new JRegistry();
            $registry->loadString($params);
            $channel->params = $registry;
        } elseif ((isset($channel->xtform)) && (is_object($channel->xtform))) {
            $params = $channel->xtform;
            unset($channel->xtform);

            $channel->params = $params;
        } else {
            throw new Exception('Invalid channel!');
        }

        $this->channel = $channel;
    }

    /**
     * sendMessage.
     *
     * @param string $message Params
     * @param object $data    Params
     *
     * @return bool
     */
    abstract public function sendMessage($message, $data);

    /**
     * getChannelId.
     *
     * @return int
     */
    public function getChannelId()
    {
        return $this->channel->id;
    }

    /**
     * getChannelType.
     *
     * @return string
     */
    public function getChannelType()
    {
        return $this->channel->channeltype_id;
    }

    /**
     * getChannelType.
     *
     * @return string
     */
    public function getChannelName()
    {
        return $this->channel->name;
    }

    /**
     * getChannelDesc.
     *
     * @return string
     */
    public function getChannelDesc()
    {
        return $this->channel->description;
    }

    /**
     * isAutopublish.
     *
     * @return bool
     */
    public function isAutopublish()
    {
        return $this->channel->autopublish;
    }

    /**
     * isPublished.
     *
     * @return bool
     */
    public function isPublished()
    {
        return $this->channel->published;
    }

    /**
     * showUrl.
     *
     * @return string
     */
    public function showUrl()
    {
        $showUrl = $this->channel->params->get('show_url', PostShareManager::STATICTEXT_END);

        return ('selected' === $showUrl) ? PostShareManager::STATICTEXT_END : $showUrl;
    }

    /**
     * getMediaMode.
     *
     * @return int
     */
    public function getMediaMode()
    {
        return $this->channel->media_mode;
    }

    /**
     * get.
     *
     * @param string $property params
     * @param mixed  $default  params
     *
     * @return mixed
     */
    public function get($property, $default = null)
    {
        return $this->channel->params->get($property, $default);
    }

    /**
     * getField.
     *
     * @param string $property params
     * @param mixed  $default  params
     *
     * @return mixed
     */
    public function getField($property, $default = null)
    {
        if (method_exists($this->channel, 'get')) {
            return $this->channel->get($property, $default);
        }

        if (isset($this->channel->{$property})) {
            return $this->channel->{$property};
        }

        return $default;
    }

    /**
     * getMaxChars.
     *
     * @return int
     */
    public function getMaxChars()
    {
        if ($this->cached_max_chars) {
            return $this->cached_max_chars;
        }

        $channeltype = XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel')->getTable();
        $channeltype->reset();
        $channeltype->load($this->channel->channeltype_id);
        $this->cached_max_chars = $channeltype->max_chars;

        return $channeltype->max_chars;
    }

    /**
     * hasWeight.
     *
     * @return bool
     */
    public function hasWeight()
    {
        return false;
    }

    /**
     * includeHashTags.
     *
     * @return bool
     */
    public function includeHashTags()
    {
        return $this->channel->params->get('hashtags', false);
    }

    /**
     * renderPost.
     *
     * @param int    $channelid         Param
     * @param string $channelTypeLayout Param
     * @param string $message           Param
     * @param string $data              Param
     *
     * @return string
     */
    public function renderPost($channelid, $channelTypeLayout, $message, $data)
    {
        $data = [
            'message' => $message,
            'data' => $data,
        ];

        return JLayoutHelper::render($channelTypeLayout, $data, JPATH_AUTOTWEET_LAYOUTS);
    }

    public function isMediaModeTextOnlyPost()
    {
        return SelectControlHelper::MEDIA_MODE_TEXT_ONLY_POST === $this->getMediaMode();
    }

    public function isMediaModePostWithImage()
    {
        return !$this->isMediaModeTextOnlyPost();
    }
}
