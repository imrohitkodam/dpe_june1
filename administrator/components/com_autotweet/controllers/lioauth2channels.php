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
 * AutotweetControllerLiOAuth2Channels.
 *
 * @since       1.0
 */
class AutotweetControllerLiOAuth2Channels extends ExtlyController
{
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
        return \Joomla\CMS\Uri\Uri::base().'index.php?option=com_autotweet&_token='.\Joomla\CMS\Factory::getSession()->getFormToken();
    }

    /**
     * callback.
     */
    public function callback()
    {
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        try {
            $session = \Joomla\CMS\Factory::getSession();
            $channelId = $session->get('channelId');

            // Invalidating
            $session->set('channelId', false);

            $code = $this->input->getString('code');
            $state = $this->input->getString('state');

            if (!empty($code)) {
                $channel = XTF0FTable::getAnInstance('Channel', 'AutoTweetTable');
                $result = $channel->load($channelId);

                if (!$result) {
                    throw new Exception('LinkedIn '.JText::_('COM_AUTOTWEET_CHANNEL_NOTLOADED'));
                }

                $lioauth2ChannelHelper = new LiOAuth2ChannelHelper($channel);
                $lioauth2ChannelHelper->authenticate($code, $state);

                // Redirect
                $url = 'index.php?option=com_autotweet&view=channels&task=edit&id='.$channelId;
                $this->setRedirect($url);
                $this->redirect();
            }
        } catch (Exception $e) {
            $logger = AutotweetLogger::getInstance();
            $logger->log(\Joomla\CMS\Log\Log::ERROR, $e->getMessage());

            throw $e;
        }
    }
}
