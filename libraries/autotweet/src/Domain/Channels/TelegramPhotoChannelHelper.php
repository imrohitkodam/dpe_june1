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
 * TelegramPhotoChannelHelper.
 *
 * @since       1.0
 */
class TelegramPhotoChannelHelper extends ChannelHelper
{
    protected $telegramClient;

    protected $botToken;

    protected $chatId;

    protected $me;

    /**
     * ChannelHelper.
     *
     * @param object $channel  params
     * @param string $botToken params
     */
    public function __construct($channel, $botToken = null)
    {
        parent::__construct($channel);

        if ($channel->id) {
            $this->botToken = $this->channel->params->get('bot_token');
            $this->chatId = $this->channel->params->get('chat_id');
        }

        if ($botToken) {
            $this->botToken = $botToken;
        }
    }

    /**
     * isAuth().
     *
     * @param string $chatId Param
     *
     * @return bool
     */
    public function isAuth($chatId = null)
    {
        if (empty($this->botToken)) {
            $this->botToken = null;

            return false;
        }

        try {
            $this->getApiInstance();

            $response = $this->telegramClient->getMe();

            $botId = $response->getId();
            $firstName = $response->getFirstName();
            $username = $response->getUsername();

            if ((int) $botId) {
                $user = $response;

                if ($chatId) {
                    $chatAlias = '-' === $chatId[0] ? $chatId : '@'.$chatId;
                    $response = $this->telegramClient->sendChatAction(
                        [
                            'chatId' => $chatAlias,
                            'action' => 'typing',
                        ]
                    );
                }

                return $user;
            }

            return false;
        } catch (Exception $e) {
            $logger = AutotweetLogger::getInstance();
            $logger->log(\Joomla\CMS\Log\Log::ERROR, $e->getMessage());

            // Just in case, it is shown someday
            \Joomla\CMS\Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }

        return false;
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
        $logger = AutotweetLogger::getInstance();
        $logger->log(\Joomla\CMS\Log\Log::INFO, 'sendTelegramMessage', $message);

        $isAuth = $this->isAuth();

        if (!$isAuth) {
            return [
                false,
                JText::_('COM_AUTOTWEET_CHANNEL_TELEGRAM_NOT_AUTH_ERR'),
            ];
        }

        $result = [false, 'Telegram Unknown Error', null];

        try {
            $this->getApiInstance();
            $chatId = '@'.$this->channel->params->get('chat_id');
            $content = $this->renderPost($this->channel->id, 'pro.channels.telegram-photo', $message, $data);
            $caption = TextUtil::telegramPhotoCaptionFilter($content);

            // Send the photo - https://telegram-bot-sdk.readme.io/reference/sendphoto
            $response = $this->telegramClient->sendPhoto([
                'chat_id' => $chatId,
                'photo' => \XTS_BUILD\Telegram\Bot\FileUpload\InputFile::create($data->image_url),
                'caption' => $caption,
            ]);

            $messageId = $response->getMessageId();

            $result = [
                true,
            ];

            $result[] = 'OK - '.$messageId;
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
        return 'https://telegram.me/'.$user->getUsername();
    }

    /**
     * Internal service functions.
     *
     * @return object
     */
    protected function getApiInstance()
    {
        if (!$this->telegramClient) {
            $this->telegramClient = new \XTS_BUILD\Telegram\Bot\Api($this->botToken);
        }

        return $this->telegramClient;
    }
}
