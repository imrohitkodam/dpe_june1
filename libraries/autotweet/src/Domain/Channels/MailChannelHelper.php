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
 * AutoTweet e-mail channel.
 *
 * @since       1.0
 */
class MailChannelHelper extends ChannelHelper
{
    public const MAX_CHARS_SUBJECT = 76;

    /**
     * sendMessage.
     *
     * @param string $message Param
     * @param string $data    Param
     *
     * @return bool
     */
    public function sendMessage($message, $data)
    {
        AutotweetLogger::getInstance()->log(\Joomla\CMS\Log\Log::INFO, 'sendMailMessage', $message);

        $recipient_mail = $this->get('mail_recipient_email');

        // Prepare email and send try to send it
        $mailSubject = TextUtil::truncString($data->title, self::MAX_CHARS_SUBJECT);
        $mailBody = $this->renderPost($this->channel->id, 'free.channels.mail-post', $message, $data);

        $config = \Joomla\CMS\Factory::getConfig();
        $result = \Joomla\CMS\Factory::getMailer()->sendMail(
            $config->get('mailfrom'),
            $config->get('fromname'),
            $recipient_mail,
            $mailSubject,
            $mailBody,
            true
        );

        if ((bool) $result) {
            $result = [
                true,
                JText::_('JPUBLISHED'),
            ];
        } else {
            if ((is_object($result)) && (method_exists($result, 'toString'))) {
                $result = [
                    false,
                    $result->toString(),
                ];
            } else {
                $result = [
                    false,
                    'Unknown error',
                ];
            }
        }

        return $result;
    }
}
