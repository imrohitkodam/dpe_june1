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
 * ChannelFactory class.
 *
 * Factory to create channel classes.
 * This is the central point to get and handle channel classes. Also all needed files are included here.
 *
 * @since       1.0
 */
class ChannelFactory
{
    private $input;

    private $channels_filter;

    private $active_channels_filter;

    private $allowed_channels;

    /**
     * ChannelFactory.
     */
    protected function __construct()
    {
        if (XTF0FPlatform::getInstance()->isCli()) {
            $this->input = new JInputCli();
        } else {
            $this->input = new \Joomla\CMS\Input\Input($_REQUEST);
        }

        $this->channels_filter = $this->input->get('channels_filter', '', 'string');
        $this->active_channels_filter = (!empty($this->channels_filter));

        if ($this->active_channels_filter) {
            $this->allowed_channels = TextUtil::listToArray($this->channels_filter);
            $this->active_channels_filter = (!empty($this->allowed_channels));
        }
    }

    /**
     * getInstance.
     *
     * @return object
     */
    public static function getInstance()
    {
        return new self();
    }

    /**
     * getChannels.
     *
     * @return array
     */
    public function getChannels()
    {
        $channels = XTF0FModel::getTmpInstance('Channels', 'AutoTweetModel');
        $channels->set('published', true);
        $channels->set('filter_order', 'ordering');
        $channels->set('filter_order_Dir', 'ASC');

        $list = $channels->getItemList(true);

        $channels = [];
        $logger = AutotweetLogger::getInstance();

        foreach ($list as $channel) {
            if (($this->active_channels_filter) && ('S' === $channel->scope)) {
                if (in_array($channel->id, $this->allowed_channels, true)) {
                    $c = $this->createChannel($channel, true);

                    if ($c) {
                        $channels[$channel->id] = $c;
                    }
                } else {
                    $logger->log(\Joomla\CMS\Log\Log::INFO, 'getChannels: channel '.$channel->id.' has been filtered out. Filter: ', $this->allowed_channels);
                }
            } else {
                $c = $this->createChannel($channel, true);

                if ($c) {
                    $channels[$channel->id] = $c;
                }
            }
        }

        $channels_ids = array_keys($channels);
        $logger->log(\Joomla\CMS\Log\Log::INFO, 'ChannelFactory getChannels', $channels_ids);

        return $channels;
    }

    /**
     * getChannel.
     *
     * @param int $id Param
     *
     * @return object
     */
    public function getChannel($id)
    {
        $channels = XTF0FModel::getTmpInstance('Channels', 'AutoTweetModel');
        $channel = $channels->getItem($id);

        return $this->createChannel($channel);
    }

    /**
     * createChannel.
     *
     * @param XTF0FTable &$channel     Param
     * @param bool       $channel_freq Param
     *
     * @return object
     */
    protected function createChannel(&$channel, $channel_freq = false)
    {
        $channeltype = XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel');
        $classname = $channeltype->getChannelClass($channel->channeltype_id);

        // Unable to load the channel and the associated classname
        if (empty($classname)) {
            return false;
        }

        if (!class_exists($classname)) {
            throw new Exception(JText::sprintf('COM_AUTOTWEET_UNABLETO', 'channel type ('.$channel->channeltype_id.')'));
        }

        if ((PERFECT_PUB_PRO) && ($channel_freq)) {
            $xtform = new JRegistry();
            $xtform->loadString($channel->params);
            $channel_freq_mhdmd = $xtform->get('channel_freq_mhdmd');

            if ((!empty($channel_freq_mhdmd)) && ('* * * * *' !== $channel_freq_mhdmd)) {
                $automators = XTF0FModel::getTmpInstance('Automators', 'AutoTweetModel');
                $key = 'channel-'.$channel->id;

                if (!$automators->lastRunCheckFreqMhdmd($key, $channel_freq_mhdmd)) {
                    $logger = AutotweetLogger::getInstance();
                    $logger->log(\Joomla\CMS\Log\Log::INFO, 'getChannel: channel '.$channel->id.' has been filtered out. Filter mhdmd: '.$channel_freq_mhdmd);

                    return null;
                }
            }
        }

        $c = new $classname($channel);

        return $c;
    }
}
