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
 * AutotweetControllerPinterestChannels.
 *
 * @since       1.0
 */
class AutotweetControllerPinterestChannels extends ExtlyController
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
        return \Joomla\CMS\Uri\Uri::base().'index.php?option=com_autotweet';
    }

    /**
     * callback.
     */
    public function callback()
    {
        // CSRF prevention disabled, we are trusting in code authentication

        /*
        if ($this->csrfProtection)
        {
            $this->_csrfProtection();
        }
        */

        try {
            // $channelId = $this->input->getUint('channelId');

            $session = \Joomla\CMS\Factory::getSession();
            $channelId = $session->get('channelId');

            // Invalidating
            $session->set('channelId', false);

            $pinterestcode = $this->input->getString('code');

            // Error throw
            if (!empty($pinterestcode)) {
                $channel = XTF0FTable::getAnInstance('Channel', 'AutoTweetTable');
                $result = $channel->load($channelId);

                if (!$result) {
                    throw new Exception(JText::_('COM_AUTOTWEET_CHANNEL_NOTLOADED'));
                }

                $pinterestChannelHelper = new PinterestChannelHelper($channel);
                $pinterestChannelHelper->authenticate($pinterestcode);

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
