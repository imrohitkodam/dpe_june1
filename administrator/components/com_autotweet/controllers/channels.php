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

require_once 'default.php';

/**
 * AutotweetControllerChannels.
 *
 * @since       1.0
 */
class AutotweetControllerChannels extends AutotweetControllerDefault
{
    // Facebook Params
    private $_app_id;

    private $_secret;

    private $_access_token;

    private $_ownapp;

    private $_channel_id;

    private $_channel_access_token;

    // LinkedIn Params
    private $_api_key;

    private $_secret_key;

    private $_oauth_user_token;

    private $_oauth_user_secret;

    /**
     * getParamsForm.
     */
    public function getParamsForm()
    {
        @ob_end_clean();
        header('Content-type: text/plain');

        // No JInputJSON in J2.5
        $raw = file_get_contents('php://input');
        $data = TextUtil::json_decode($raw, true);

        $safeHtmlFilter = JFilterInput::getInstance();

        $token = $data['token'];
        $token = $safeHtmlFilter->clean($token, 'ALNUM');
        $this->input->set($token, 1);

        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $channeltype_id = $data['channelTypeId'];
        $channeltype_id = $safeHtmlFilter->clean($channeltype_id, 'ALNUM');
        $channel_id = $data['channelId'];
        $channel_id = $safeHtmlFilter->clean($channel_id, 'ALNUM');

        // Load the model
        $channeltype = XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel');

        if ((!PERFECT_PUB_PRO) && $channeltype->isProChannel($channeltype_id)) {
            $result = JText::_('COM_AUTOTWEET_UPDATE_TO_PERFECT_PUBLISHER_PRO_LABEL');
        } else {
            $view = 'nochannel';

            if ($channeltype_id) {
                $view = $channeltype->getParamsForm($channeltype_id);
            }

            $config = [
                'input' => [
                    'option' => 'com_autotweet',
                    'view' => $view,
                    'task' => (empty($channel_id) ? 'add' : 'edit'),
                    'id' => $channel_id,
                    'channeltype_id' => $channeltype_id,
                ],
                'modelName' => 'AutotweetModelChannels',
            ];

            @ob_start();
            XTF0FDispatcher::getTmpInstance('com_autotweet', $view, $config)->dispatch();
            $result = ob_get_contents();
            @ob_end_clean();
        }

        echo TextUtil::encodeJsonSuccessPackage($result);
        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }

    /**
     * getFbValidation.
     */
    public function getFbValidation()
    {
        @ob_end_clean();
        header('Content-type: text/plain');

        $this->_loadFbParams();

        $status = false;
        $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', __FUNCTION__);
        $user = null;
        $tokenInfo = null;

        try {
            $fbAppHelper = new FbAppHelper($this->_app_id, $this->_secret, $this->_access_token);

            if ($fbAppHelper->login()) {
                $user = $fbAppHelper->getUser();
                $result = $fbAppHelper->verify();
                $tokenInfo = $fbAppHelper->getDebugToken();

                $status = $result[0];
                $result_message = $result[1];

                $message = [
                    'message' => $result_message,
                    'user' => $user,
                    'tokenInfo' => $tokenInfo,
                ];
                echo TextUtil::encodeJsonSuccessPackage($message);
            } else {
                $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', 'Facebook login');
                echo TextUtil::encodeJsonErrorPackage($result_message);
            }
        } catch (Exception $e) {
            $result_message = $e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($result_message);
        }

        // $logger = AutotweetLogger::getInstance();
        // $logger->log(\Joomla\CMS\Log\Log::INFO, "getFbValidation: " . $result_message);

        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }

    /**
     * getFbChValidation.
     */
    public function getFbChValidation()
    {
        @ob_end_clean();
        header('Content-type: text/plain');

        $this->_loadFbParams();

        $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', __FUNCTION__);
        $tokenInfo = null;

        try {
            $fbAppHelper = new FbAppHelper($this->_app_id, $this->_secret, $this->_access_token);

            if ($fbAppHelper->login()) {
                $tokenInfo = $fbAppHelper->getDebugToken($this->_fbchannel_access_token);
                $result_message = JText::_('COM_AUTOTWEET_OK');

                $message = [
                    'message' => $result_message,
                    'tokenInfo' => $tokenInfo,
                ];
                echo TextUtil::encodeJsonSuccessPackage($message);
            } else {
                $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', 'Facebook login');
                echo TextUtil::encodeJsonErrorPackage($result_message);
            }
        } catch (Exception $e) {
            $result_message = $e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($result_message);
        }

        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }

    /**
     * getFbExtend.
     */
    public function getFbExtend()
    {
        @ob_end_clean();
        header('Content-type: text/plain');

        $this->_loadFbParams();

        $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', __FUNCTION__);
        $extended_token = null;
        $user = null;
        $tokenInfo = null;

        try {
            $fbAppHelper = new FbAppHelper($this->_app_id, $this->_secret, $this->_access_token);

            if ($fbAppHelper->login()) {
                $extended_token = $fbAppHelper->getExtendedAccessToken();

                if ($extended_token) {
                    $this->_access_token = $extended_token;

                    $fbAppHelper = new FbAppHelper($this->_app_id, $this->_secret, $this->_access_token);

                    if ($fbAppHelper->login(true)) {
                        $tokenInfo = $fbAppHelper->getDebugToken();
                        $user = $fbAppHelper->getUser();
                        $result_message = JText::_('COM_AUTOTWEET_OK');

                        $message = [
                            'message' => $result_message,
                            'extended_token' => $extended_token,
                            'user' => [
                                'id' => $user->getId(),
                                'name' => $user->getName(),
                                'link' => $user->getLink(),
                            ],
                            'tokenInfo' => $tokenInfo,
                        ];
                        $response = TextUtil::encodeJsonSuccessPackage($message);
                    } else {
                        $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', 'Facebook login (extended)');
                        $response = TextUtil::encodeJsonErrorPackage($result_message);
                    }
                } else {
                    $result_message = JText::sprintf('COM_AUTOTWEET_UNABLETO', 'extend token');
                    $response = TextUtil::encodeJsonErrorPackage($result_message);
                }
            } else {
                $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', 'Facebook login');
                $response = TextUtil::encodeJsonErrorPackage($result_message);
            }
        } catch (Exception $e) {
            $result_message = $e->getMessage();
            $response = TextUtil::encodeJsonErrorPackage($result_message);
        }

        // $logger = AutotweetLogger::getInstance();
        // $logger->log(\Joomla\CMS\Log\Log::INFO, "getFbExtend: " . $response);

        echo $response;
        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }

    /**
     * getTwValidation.
     */
    public function getTwValidation()
    {
        @ob_end_clean();
        header('Content-type: text/plain');

        // No JInputJSON in J2.5
        $raw = file_get_contents('php://input');
        $data = TextUtil::json_decode($raw, true);

        $safeHtmlFilter = JFilterInput::getInstance();

        $token = $data['token'];
        $token = $safeHtmlFilter->clean($token, 'ALNUM');
        $this->input->set($token, 1);

        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $consumer_key = $data['consumer_key'];
        $consumer_key = $safeHtmlFilter->clean($consumer_key, 'ALNUM');

        $consumer_secret = $data['consumer_secret'];
        $consumer_secret = $safeHtmlFilter->clean($consumer_secret, 'ALNUM');

        $access_token = $data['access_token'];
        $access_token = $safeHtmlFilter->clean($access_token, 'CMD');

        $access_token_secret = $data['access_token_secret'];
        $access_token_secret = $safeHtmlFilter->clean($access_token_secret, 'CMD');

        $status = false;
        $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', __FUNCTION__);
        $user = null;
        $url = null;
        $icon = null;

        try {
            $appHelper = new TwAppHelper($consumer_key, $consumer_secret, $access_token, $access_token_secret);

            if ($result = $appHelper->verify()) {
                $status = $result['status'];
                $result_message = $result['error_message'];

                if ($status) {
                    $result_message = $result['error_message'];
                    $user = $result['user'];
                    $url = $result['url'];

                    $icon = XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel')
                        ->getIcon(AutotweetModelChanneltypes::TYPE_TW_CHANNEL);

                    $message = [
                        'message' => $result_message,
                        'user' => $user,
                        'icon' => $icon,
                        'url' => $url,
                    ];
                    echo TextUtil::encodeJsonSuccessPackage($message);
                } else {
                    echo TextUtil::encodeJsonErrorPackage($result_message);
                }
            } else {
                $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', 'Twitter login');
                echo TextUtil::encodeJsonErrorPackage($result_message);
            }
        } catch (Exception $e) {
            $result_message = $e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($result_message);
        }

        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }

    /**
     * getFbChannels.
     */
    public function getFbChannels()
    {
        @ob_end_clean();
        header('Content-type: text/plain');

        $this->_loadFbParams();

        $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', __FUNCTION__);
        $icon = null;

        try {
            $fbAppHelper = new FbAppHelper($this->_app_id, $this->_secret, $this->_access_token);

            if ($fbAppHelper->login()) {
                XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel');
                $channels = $fbAppHelper->getChannels();

                $result_message = JText::_('COM_AUTOTWEET_OK');

                $icon = XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel')
                    ->getIcon(AutotweetModelChanneltypes::TYPE_FB_CHANNEL);

                $message = [
                    'message' => $result_message,
                    'channels' => $channels,
                    'icon' => $icon,
                ];
                $response = TextUtil::encodeJsonSuccessPackage($message);
            } else {
                $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', 'Facebook login');
                $response = TextUtil::encodeJsonErrorPackage($result_message);
            }
        } catch (Exception $e) {
            $result_message = $e->getMessage();
            $response = TextUtil::encodeJsonErrorPackage($result_message);
        }

        // $logger = AutotweetLogger::getInstance();
        // $logger->log(\Joomla\CMS\Log\Log::INFO, "getFbChannels: " . $response);

        echo $response;
        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }

    /**
     * getLiOAuth2Validation.
     */
    public function getLiOAuth2Validation()
    {
        @ob_end_clean();
        header('Content-type: text/plain');

        // No JInputJSON in J2.5
        $raw = file_get_contents('php://input');
        $data = TextUtil::json_decode($raw, true);

        $safeHtmlFilter = JFilterInput::getInstance();

        $token = $data['token'];
        $token = $safeHtmlFilter->clean($token, 'ALNUM');
        $this->input->set($token, 1);

        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $channel_id = $data['channel_id'];
        $channel_id = $safeHtmlFilter->clean($channel_id, 'ALNUM');

        $channel = XTF0FTable::getAnInstance('Channel', 'AutoTweetTable');
        $result = $channel->load($channel_id);
        $channel->xtform = EForm::paramsToRegistry($channel);

        $status = false;
        $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', __FUNCTION__);
        $user = null;
        $userId = null;
        $url = null;
        $icon = null;

        try {
            $liOAuth2ChannelHelper = new LiOAuth2ChannelHelper($channel);
            $isAuth = $liOAuth2ChannelHelper->isAuth();

            if (($isAuth) && (is_array($isAuth)) && (array_key_exists('user', $isAuth))) {
                $user = $isAuth['user'];
                $result_message = JText::_('COM_AUTOTWEET_OK');
                $url = $liOAuth2ChannelHelper->getSocialUrl($user);
                $userId = $user->id;
                $icon = XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel')
                    ->getIcon(AutoTweetModelChanneltypes::TYPE_LIOAUTH2_CHANNEL);

                $message = [
                    'message' => $result_message,
                    'user' => $userId,
                    'icon' => $icon,
                    'url' => $url,
                ];
                echo TextUtil::encodeJsonSuccessPackage($message);
            } else {
                $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', 'LinkedIn OAuth2');
                echo TextUtil::encodeJsonErrorPackage($result_message);
            }
        } catch (Exception $e) {
            $result_message = $e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($result_message);
        }

        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }

    /**
     * getLiOAuth2Companies.
     */
    public function getLiOAuth2Companies()
    {
        @ob_end_clean();
        header('Content-type: text/plain');

        // No JInputJSON in J2.5
        $raw = file_get_contents('php://input');
        $data = TextUtil::json_decode($raw, true);

        $safeHtmlFilter = JFilterInput::getInstance();

        $token = $data['token'];
        $token = $safeHtmlFilter->clean($token, 'ALNUM');
        $this->input->set($token, 1);

        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $channel_id = $data['channel_id'];
        $channel_id = $safeHtmlFilter->clean($channel_id, 'ALNUM');

        $channel = XTF0FTable::getAnInstance('Channel', 'AutoTweetTable');
        $result = $channel->load($channel_id);
        $channel->xtform = EForm::paramsToRegistry($channel);

        $status = false;
        $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', __FUNCTION__);
        $icon = null;

        try {
            $lioauth2ChannelHelper = new LiOAuth2CompanyChannelHelper($channel);
            $channels = $lioauth2ChannelHelper->getMyCompanies();
            $icon = XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel')
                ->getIcon(AutotweetModelChanneltypes::TYPE_LIOAUTH2COMPANY_CHANNEL);
            $status = true;
            $result_message = JText::_('COM_AUTOTWEET_OK');
        } catch (Exception $e) {
            $result_message = $e->getMessage();
        }

        if ((2 === count($channels)) && (false === (bool) $channels[0])) {
            echo TextUtil::encodeJsonErrorPackage($channels[1]);
        } else {
            if ($status) {
                $message = [
                    'message' => $result_message,
                    'channels' => $channels,
                    'icon' => $icon,
                ];
                echo TextUtil::encodeJsonSuccessPackage($message);
            } else {
                echo TextUtil::encodeJsonErrorPackage($result_message);
            }
        }

        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }

    /**
     * getBloggerValidation.
     */
    public function getBloggerValidation()
    {
        @ob_end_clean();
        header('Content-type: text/plain');

        // No JInputJSON in J2.5
        $raw = file_get_contents('php://input');
        $data = TextUtil::json_decode($raw, true);

        $safeHtmlFilter = JFilterInput::getInstance();

        $token = $data['token'];
        $token = $safeHtmlFilter->clean($token, 'ALNUM');
        $this->input->set($token, 1);

        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $channel_id = $data['channel_id'];
        $channel_id = $safeHtmlFilter->clean($channel_id, 'ALNUM');

        $status = false;
        $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', __FUNCTION__);

        $channel = XTF0FTable::getAnInstance('Channel', 'AutoTweetTable');
        $result = $channel->load($channel_id);

        $user = null;
        $url = null;
        $icon = null;

        if (!$result) {
            $result_message = JText::_('COM_AUTOTWEET_CHANNEL_NOTLOADED');
        } else {
            try {
                $bloggerChannelHelper = new BloggerChannelHelper($channel);
                $isAuth = $bloggerChannelHelper->isAuth();

                if ($isAuth) {
                    $result_message = JText::_('COM_AUTOTWEET_OK');
                    $user = $bloggerChannelHelper->getUser();
                    $url = $user['url'];
                    $icon = XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel')
                        ->getIcon(AutotweetModelChanneltypes::TYPE_BLOGGER_CHANNEL);

                    $message = [
                        'message' => $result_message,
                        'user' => $user,
                        'social_icon' => $icon,
                        'social_url' => $url,
                    ];
                    echo TextUtil::encodeJsonSuccessPackage($message);
                } else {
                    $result_message = JText::_('COM_AUTOTWEET_CHANNEL_BLOGGER_NOT_AUTH_ERR');
                    echo TextUtil::encodeJsonErrorPackage($result_message);
                }
            } catch (Exception $e) {
                $result_message = $e->getMessage();
                echo TextUtil::encodeJsonErrorPackage($result_message);
            }
        }

        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }

    /**
     * getScoopitValidation.
     */
    public function getScoopitValidation()
    {
        @ob_end_clean();
        header('Content-type: text/plain');

        // No JInputJSON in J2.5
        $raw = file_get_contents('php://input');
        $data = TextUtil::json_decode($raw, true);

        $safeHtmlFilter = JFilterInput::getInstance();

        $token = $data['token'];
        $token = $safeHtmlFilter->clean($token, 'ALNUM');
        $this->input->set($token, 1);

        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $channel_id = $data['channel_id'];
        $channel_id = $safeHtmlFilter->clean($channel_id, 'ALNUM');

        $channel = XTF0FTable::getAnInstance('Channel', 'AutoTweetTable');
        $result = $channel->load($channel_id);
        $channel->xtform = EForm::paramsToRegistry($channel);

        $consumer_key = $data['consumer_key'];
        $consumer_key = $safeHtmlFilter->clean($consumer_key, 'STRING');
        $channel->xtform->set('consumer_key', $consumer_key);

        $consumer_secret = $data['consumer_secret'];
        $consumer_secret = $safeHtmlFilter->clean($consumer_secret, 'STRING');
        $channel->xtform->set('consumer_secret', $consumer_secret);

        $access_token = $data['access_token'];
        $access_token = $safeHtmlFilter->clean($access_token, 'STRING');
        $channel->xtform->set('access_token', $access_token);

        $access_secret = $data['access_secret'];
        $access_secret = $safeHtmlFilter->clean($access_secret, 'STRING');
        $channel->xtform->set('access_secret', $access_secret);

        $status = false;
        $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', __FUNCTION__);
        $user = null;
        $url = null;
        $icon = null;

        try {
            $scoopitChannelHelper = new ScoopitChannelHelper($channel);
            $isAuth = $scoopitChannelHelper->isAuth();

            if ($isAuth) {
                $status = $isAuth->success;
                $result_message = $isAuth->status;
                $user = $isAuth->connectedUser;
                $url = $scoopitChannelHelper->getSocialUrl($user);
                $icon = XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel')
                    ->getIcon(AutoTweetModelChanneltypes::TYPE_SCOOPIT_CHANNEL);

                $message = [
                    'message' => $result_message,
                    'user' => $user,
                    'icon' => $icon,
                    'url' => $url,
                ];
                echo TextUtil::encodeJsonSuccessPackage($message);
            } else {
                $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', 'Scoopit login');
                echo TextUtil::encodeJsonErrorPackage($result_message);
            }
        } catch (Exception $e) {
            $result_message = $e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($result_message);
        }

        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }

    /**
     * getScoopitTopics.
     */
    public function getScoopitTopics()
    {
        @ob_end_clean();
        header('Content-type: text/plain');

        // No JInputJSON in J2.5
        $raw = file_get_contents('php://input');
        $data = TextUtil::json_decode($raw, true);

        $safeHtmlFilter = JFilterInput::getInstance();

        $token = $data['token'];
        $token = $safeHtmlFilter->clean($token, 'ALNUM');
        $this->input->set($token, 1);

        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $channel_id = $data['channel_id'];
        $channel_id = $safeHtmlFilter->clean($channel_id, 'ALNUM');

        $channel = XTF0FTable::getAnInstance('Channel', 'AutoTweetTable');
        $result = $channel->load($channel_id);
        $channel->xtform = EForm::paramsToRegistry($channel);

        $search = $data['search'];
        $search = $safeHtmlFilter->clean($search, 'STRING');

        $status = false;
        $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', __FUNCTION__);
        $topics = null;

        try {
            if (empty($search)) {
                throw new Exception(JText::_('COM_AUTOTWEET_CHANNEL_SCOOPIT_TOPIC_REQUIRED_ERR'));
            }

            $scoopitChannelHelper = new ScoopitChannelHelper($channel);
            $topics = $scoopitChannelHelper->searchTopics($search);
            $result_message = JText::_('COM_AUTOTWEET_OK');

            $message = [
                'message' => $result_message,
                'topics' => $topics,
            ];
            echo TextUtil::encodeJsonSuccessPackage($message);
        } catch (Exception $e) {
            $result_message = $e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($result_message);
        }

        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }

    /**
     * getTelegramValidation.
     */
    public function getTelegramValidation()
    {
        @ob_end_clean();
        header('Content-type: text/plain');

        // No JInputJSON in J2.5
        $raw = file_get_contents('php://input');
        $data = TextUtil::json_decode($raw, true);

        $safeHtmlFilter = JFilterInput::getInstance();

        $token = $data['token'];
        $token = $safeHtmlFilter->clean($token, 'ALNUM');
        $this->input->set($token, 1);

        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $botToken = $data['bot_token'];
        $botToken = $safeHtmlFilter->clean($botToken, 'STRING');

        $chatId = $data['chat_id'];
        $chatId = $safeHtmlFilter->clean($chatId, 'STRING');

        $channel = XTF0FTable::getAnInstance('Channel', 'AutoTweetTable');
        $channel->xtform = EForm::paramsToRegistry($channel);

        $status = false;
        $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', __FUNCTION__);
        $user = null;
        $userId = null;
        $url = null;
        $icon = null;

        try {
            $telegramChannelHelper = new TelegramChannelHelper($channel, $botToken);
            $user = $telegramChannelHelper->isAuth($chatId);

            if ($user) {
                $resultMessage = JText::_('COM_AUTOTWEET_OK');
                $url = $telegramChannelHelper->getSocialUrl($user);
                $userId = $user->getId();
                $icon = XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel')
                    ->getIcon(AutoTweetModelChanneltypes::TYPE_TELEGRAM_CHANNEL);

                $message = [
                    'message' => $resultMessage,
                    'user' => $userId,
                    'icon' => $icon,
                    'url' => $url,
                ];
                echo TextUtil::encodeJsonSuccessPackage($message);
            } else {
                $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', 'Telegram login').' (Is the bot a member of the channel?)';
                echo TextUtil::encodeJsonErrorPackage($result_message);
            }
        } catch (Exception $e) {
            $result_message = $e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($result_message);
        }

        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }

    /**
     * getMediumValidation.
     */
    public function getMediumValidation()
    {
        @ob_end_clean();
        header('Content-type: text/plain');

        // No JInputJSON in J2.5
        $raw = file_get_contents('php://input');
        $data = TextUtil::json_decode($raw, true);

        $safeHtmlFilter = JFilterInput::getInstance();

        $token = $data['token'];
        $token = $safeHtmlFilter->clean($token, 'ALNUM');
        $this->input->set($token, 1);

        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $integrationToken = $data['integration_token'];
        $integrationToken = $safeHtmlFilter->clean($integrationToken, 'STRING');

        $channel = XTF0FTable::getAnInstance('Channel', 'AutoTweetTable');
        $channel->xtform = EForm::paramsToRegistry($channel);

        $status = false;
        $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', __FUNCTION__);
        $user = null;
        $userId = null;
        $url = null;
        $icon = null;

        try {
            $mediumChannelHelper = new MediumChannelHelper($channel, $integrationToken);
            $user = $mediumChannelHelper->isAuth();

            if ($user) {
                $resultMessage = JText::_('COM_AUTOTWEET_OK');
                $url = $mediumChannelHelper->getSocialUrl($user);
                $userId = $user->data->id;
                $icon = XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel')
                    ->getIcon(AutoTweetModelChanneltypes::TYPE_MEDIUM_CHANNEL);

                $message = [
                    'message' => $resultMessage,
                    'user' => $userId,
                    'icon' => $icon,
                    'url' => $url,
                ];
                echo TextUtil::encodeJsonSuccessPackage($message);
            } else {
                $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', 'Medium login');
                echo TextUtil::encodeJsonErrorPackage($result_message);
            }
        } catch (Exception $e) {
            $result_message = $e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($result_message);
        }

        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }

    /**
     * getPushwooshValidation.
     */
    public function getPushwooshValidation()
    {
        @ob_end_clean();
        header('Content-type: text/plain');

        // No JInputJSON in J2.5
        $raw = file_get_contents('php://input');
        $data = TextUtil::json_decode($raw, true);

        $safeHtmlFilter = JFilterInput::getInstance();

        $token = $data['token'];
        $token = $safeHtmlFilter->clean($token, 'ALNUM');
        $this->input->set($token, 1);

        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $applicationId = $data['application_id'];
        $applicationId = $safeHtmlFilter->clean($applicationId, 'STRING');

        $accessToken = $data['access_token'];
        $accessToken = $safeHtmlFilter->clean($accessToken, 'ALNUM');

        $channel = XTF0FTable::getAnInstance('Channel', 'AutoTweetTable');
        $channel->xtform = EForm::paramsToRegistry($channel);

        $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', __FUNCTION__);

        try {
            $pushwooshChannelHelper = new PushwooshChannelHelper($channel, $applicationId, $accessToken);
            $isAuth = $pushwooshChannelHelper->isAuth();

            if ($isAuth) {
                $message = [
                    'status' => true,
                ];
                echo TextUtil::encodeJsonSuccessPackage($message);
            } else {
                $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', 'Pushwoosh login');
                echo TextUtil::encodeJsonErrorPackage($result_message);
            }
        } catch (Exception $e) {
            $result_message = $e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($result_message);
        }

        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }

    /**
     * getOneSignalValidation.
     */
    public function getOneSignalValidation()
    {
        @ob_end_clean();
        header('Content-type: text/plain');

        // No JInputJSON in J2.5
        $raw = file_get_contents('php://input');
        $data = TextUtil::json_decode($raw, true);

        $safeHtmlFilter = JFilterInput::getInstance();

        $token = $data['token'];
        $token = $safeHtmlFilter->clean($token, 'ALNUM');
        $this->input->set($token, 1);

        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $appId = $data['app_id'];
        $appId = $safeHtmlFilter->clean($appId, 'STRING');

        $restApiKey = $data['rest_api_key'];
        $restApiKey = $safeHtmlFilter->clean($restApiKey, 'ALNUM');

        $userAuthKey = $data['user_auth_key'];
        $userAuthKey = $safeHtmlFilter->clean($userAuthKey, 'ALNUM');

        $channel = XTF0FTable::getAnInstance('Channel', 'AutoTweetTable');
        $channel->xtform = EForm::paramsToRegistry($channel);

        $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', __FUNCTION__);

        try {
            $oneSignalChannelHelper = new OneSignalChannelHelper($channel, $appId, $restApiKey, $userAuthKey);
            $isAuth = $oneSignalChannelHelper->isAuth();

            if ($isAuth) {
                $message = [
                    'status' => true,
                ];
                echo TextUtil::encodeJsonSuccessPackage($message);
            } else {
                $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', 'OneSignal login');
                echo TextUtil::encodeJsonErrorPackage($result_message);
            }
        } catch (Exception $e) {
            $result_message = $e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($result_message);
        }

        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }

    /**
     * getPushAlertValidation.
     */
    public function getPushAlertValidation()
    {
        @ob_end_clean();
        header('Content-type: text/plain');

        // No JInputJSON in J2.5
        $raw = file_get_contents('php://input');
        $data = TextUtil::json_decode($raw, true);

        $safeHtmlFilter = JFilterInput::getInstance();

        $token = $data['token'];
        $token = $safeHtmlFilter->clean($token, 'ALNUM');
        $this->input->set($token, 1);

        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $restApiKey = $data['rest_api_key'];
        $restApiKey = $safeHtmlFilter->clean($restApiKey, 'ALNUM');

        $channel = XTF0FTable::getAnInstance('Channel', 'AutoTweetTable');
        $channel->xtform = EForm::paramsToRegistry($channel);

        $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', __FUNCTION__);

        try {
            $pushAlertChannelHelper = new PushAlertChannelHelper($channel, $restApiKey);
            $isAuth = $pushAlertChannelHelper->isAuth();

            if ($isAuth) {
                $message = [
                    'status' => true,
                ];
                echo TextUtil::encodeJsonSuccessPackage($message);
            } else {
                $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', 'PushAlert login');
                echo TextUtil::encodeJsonErrorPackage($result_message);
            }
        } catch (Exception $e) {
            $result_message = $e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($result_message);
        }

        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }

    /**
     * Method to get a reference to the current view and load it if necessary.
     *
     * @param string $name   The view name. Optional, defaults to the controller name.
     * @param string $type   The view type. Optional.
     * @param string $prefix The class prefix. Optional.
     * @param array  $config Configuration array for view. Optional.
     *
     * @throws Exception
     *
     * @return XTF0FView reference to the view or an error
     */
    public function getView($name = '', $type = '', $prefix = '', $config = [])
    {
        if ((array_key_exists('layout', $config)) && (strpos($config['layout'], 'channel-post') > 0)) {
            $type = 'raw';
        }

        return parent::getView($name, $type, $prefix, $config);
    }

    /**
     * getPageSpeedValidation.
     */
    public function getPageSpeedValidation()
    {
        @ob_end_clean();
        header('Content-type: text/plain');

        // No JInputJSON in J2.5
        $raw = file_get_contents('php://input');
        $data = TextUtil::json_decode($raw, true);

        $safeHtmlFilter = JFilterInput::getInstance();

        $token = $data['token'];
        $token = $safeHtmlFilter->clean($token, 'ALNUM');
        $this->input->set($token, 1);

        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $restApiKey = $data['api_key'];
        $restApiKey = $safeHtmlFilter->clean($restApiKey, 'STRING');

        $channel = XTF0FTable::getAnInstance('Channel', 'AutoTweetTable');
        $channel->xtform = EForm::paramsToRegistry($channel);

        $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', __FUNCTION__);

        try {
            $PageSpeedChannelHelper = new PageSpeedChannelHelper($channel, $restApiKey);
            $isAuth = $PageSpeedChannelHelper->isAuth();

            if ($isAuth) {
                $message = [
                    'status' => true,
                ];
                echo TextUtil::encodeJsonSuccessPackage($message);
            } else {
                $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', 'PageSpeed login');
                echo TextUtil::encodeJsonErrorPackage($result_message);
            }
        } catch (Exception $e) {
            $result_message = $e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($result_message);
        }

        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }

    /**
     * getTumblrValidation.
     *
     * @return void
     */
    public function getTumblrValidation()
    {
        @ob_end_clean();
        header('Content-type: text/plain');

        // No JInputJSON in J2.5
        $raw = file_get_contents('php://input');
        $data = TextUtil::json_decode($raw, true);

        $safeHtmlFilter = JFilterInput::getInstance();

        $token = $data['token'];
        $token = $safeHtmlFilter->clean($token, 'ALNUM');
        $this->input->set($token, 1);

        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $consumer_key = $data['consumer_key'];
        $consumer_key = $safeHtmlFilter->clean($consumer_key, 'ALNUM');

        $consumer_secret = $data['consumer_secret'];
        $consumer_secret = $safeHtmlFilter->clean($consumer_secret, 'ALNUM');

        $access_token = $data['access_token'];
        $access_token = $safeHtmlFilter->clean($access_token, 'CMD');

        $access_secret = $data['access_secret'];
        $access_secret = $safeHtmlFilter->clean($access_secret, 'CMD');

        $status = false;
        $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', __FUNCTION__);
        $user = null;
        $url = null;
        $icon = null;

        try {
            $tumblrChannelHelper = new TumblrChannelHelper(
                null,
                $consumer_key,
                $consumer_secret,
                $access_token,
                $access_secret
            );

            if ($result = $tumblrChannelHelper->verify()) {
                $result_message = $result['error_message'];
                $user = $result['user'];
                $url = $result['url'];
                $icon = XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel')
                    ->getIcon(AutoTweetModelChanneltypes::TYPE_TUMBLR_CHANNEL);
                $user->blogs = SelectControlHelper::tumblrOptions($user->blogs);

                $message = [
                    'message' => $result_message,
                    'user' => $user,
                    'icon' => $icon,
                    'url' => $url,
                ];
                echo TextUtil::encodeJsonSuccessPackage($message);
            } else {
                $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', 'Tumblr login');
                echo TextUtil::encodeJsonErrorPackage($result_message);
            }
        } catch (Exception $e) {
            $result_message = $e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($result_message);
        }

        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }

    /**
     * getPinterestValidation.
     */
    public function getPinterestValidation()
    {
        @ob_end_clean();
        header('Content-type: text/plain');

        // No JInputJSON in J2.5
        $raw = file_get_contents('php://input');
        $data = TextUtil::json_decode($raw, true);

        $safeHtmlFilter = JFilterInput::getInstance();

        $token = $data['token'];
        $token = $safeHtmlFilter->clean($token, 'STRING');
        $this->input->set($token, 1);

        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $channel_id = $data['channel_id'];
        $channel_id = $safeHtmlFilter->clean($channel_id, 'ALNUM');

        $channel = XTF0FTable::getAnInstance('Channel', 'AutoTweetTable');
        $result = $channel->load($channel_id);
        $channel->xtform = EForm::paramsToRegistry($channel);

        $status = false;
        $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', __FUNCTION__);
        $user = null;
        $userId = null;
        $url = null;
        $icon = null;

        try {
            $pinterestChannelHelper = new PinterestChannelHelper($channel);
            $isAuth = $pinterestChannelHelper->isAuth();

            if ($isAuth) {
                $user = $pinterestChannelHelper->getUser();
                $result_message = JText::_('COM_AUTOTWEET_OK');
                $url = $pinterestChannelHelper->getSocialUrl($user);
                $userId = $user->id;
                $icon = XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel')
                    ->getIcon(AutoTweetModelChanneltypes::TYPE_PINTEREST_CHANNEL);

                $message = [
                    'message' => $result_message,
                    'user' => $userId,
                    'icon' => $icon,
                    'url' => $url,
                ];
                echo TextUtil::encodeJsonSuccessPackage($message);
            } else {
                $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', 'Pinterest OAuth');
                echo TextUtil::encodeJsonErrorPackage($result_message);
            }
        } catch (Exception $e) {
            $result_message = $e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($result_message);
        }

        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }

    /**
     * getMyBusinessValidation.
     */
    public function getMyBusinessValidation()
    {
        @ob_end_clean();
        header('Content-type: text/plain');

        // No JInputJSON in J2.5
        $raw = file_get_contents('php://input');
        $data = TextUtil::json_decode($raw, true);

        $safeHtmlFilter = JFilterInput::getInstance();

        $token = $data['token'];
        $token = $safeHtmlFilter->clean($token, 'ALNUM');
        $this->input->set($token, 1);

        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $channel_id = $data['channel_id'];
        $channel_id = $safeHtmlFilter->clean($channel_id, 'ALNUM');

        $status = false;
        $result_message = JText::sprintf('COM_AUTOTWEET_FAILED', __FUNCTION__);

        $channel = XTF0FTable::getAnInstance('Channel', 'AutoTweetTable');
        $result = $channel->load($channel_id);

        $user = null;
        $url = null;
        $icon = null;

        if (!$result) {
            $result_message = JText::_('COM_AUTOTWEET_CHANNEL_NOTLOADED');
        } else {
            try {
                $myBusinessChannelHelper = new MyBusinessChannelHelper($channel);
                $isAuth = $myBusinessChannelHelper->isAuth();

                if ($isAuth) {
                    $result_message = JText::_('COM_AUTOTWEET_OK');
                    $user = $myBusinessChannelHelper->getUser();
                    $url = $user['url'];
                    $icon = XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel')
                        ->getIcon(AutotweetModelChanneltypes::TYPE_BLOGGER_CHANNEL);

                    $message = [
                        'message' => $result_message,
                        'user' => $user,
                        'social_icon' => $icon,
                        'social_url' => $url,
                    ];
                    echo TextUtil::encodeJsonSuccessPackage($message);
                } else {
                    $result_message = JText::_('COM_AUTOTWEET_CHANNEL_BLOGGER_NOT_AUTH_ERR');
                    echo TextUtil::encodeJsonErrorPackage($result_message);
                }
            } catch (Exception $e) {
                $result_message = $e->getMessage();
                echo TextUtil::encodeJsonErrorPackage($result_message);
            }
        }

        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }

    /**
     * _loadFbParams.
     */
    private function _loadFbParams()
    {
        // No JInputJSON in J2.5
        $raw = file_get_contents('php://input');
        $data = TextUtil::json_decode($raw, true);

        $safeHtmlFilter = JFilterInput::getInstance();

        $token = $data['token'];
        $token = $safeHtmlFilter->clean($token, 'ALNUM');
        $this->input->set($token, 1);

        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $this->_app_id = $data['app_id'];
        $this->_app_id = $safeHtmlFilter->clean($this->_app_id, 'ALNUM');

        $this->_secret = $data['secret'];
        $this->_secret = $safeHtmlFilter->clean($this->_secret, 'ALNUM');

        $this->_access_token = $data['access_token'];
        $this->_access_token = $safeHtmlFilter->clean($this->_access_token, 'ALNUM');

        $this->_ownapp = $data['own_app'];
        $this->_ownapp = $safeHtmlFilter->clean($this->_ownapp, 'ALNUM');

        $this->_channel_id = null;
        $this->_channel_access_token = null;

        if (array_key_exists('channel_id', $data)) {
            $this->_channel_id = $data['channel_id'];
            $this->_channel_id = $safeHtmlFilter->clean($this->_channel_id, 'ALNUM');

            $this->_channel_access_token = $data['channel_access_token'];
            $this->_channel_access_token = $safeHtmlFilter->clean($this->_channel_access_token, 'ALNUM');
        }

        if (array_key_exists('fbchannel_access_token', $data)) {
            $this->_fbchannel_access_token = $data['fbchannel_access_token'];
            $this->_fbchannel_access_token = $safeHtmlFilter->clean($this->_fbchannel_access_token, 'ALNUM');
        }

        if (!$this->_ownapp) {
            $channeltype = XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel')->getTable();
            $channeltype->reset();
            $channeltype->load(AutotweetModelChanneltypes::TYPE_FB_CHANNEL);

            $this->_app_id = $channeltype->auth_key;
            $this->_secret = $channeltype->auth_secret;
        }

        if (array_key_exists('channeltype_id', $data)) {
            $this->_channeltype_id = $data['channeltype_id'];
            $this->_channeltype_id = $safeHtmlFilter->clean($this->_channeltype_id, 'ALNUM');
        }
    }

    /**
     * _loadLiParams.
     */
    private function _loadLiParams()
    {
        // No JInputJSON in J2.5
        $raw = file_get_contents('php://input');
        $data = TextUtil::json_decode($raw, true);

        $safeHtmlFilter = JFilterInput::getInstance();

        $token = $data['token'];
        $token = $safeHtmlFilter->clean($token, 'ALNUM');
        $this->input->set($token, 1);

        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $this->_api_key = $data['api_key'];
        $this->_api_key = $safeHtmlFilter->clean($this->_api_key, 'ALNUM');

        $this->_secret_key = $data['secret_key'];
        $this->_secret_key = $safeHtmlFilter->clean($this->_secret_key, 'ALNUM');

        $this->_oauth_user_token = $data['oauth_user_token'];
        $this->_oauth_user_token = $safeHtmlFilter->clean($this->_oauth_user_token, 'CMD');

        $this->_oauth_user_secret = $data['oauth_user_secret'];
        $this->_oauth_user_secret = $safeHtmlFilter->clean($this->_oauth_user_secret, 'CMD');
    }
}
