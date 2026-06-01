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
 * PageSpeedChannelHelper.
 *
 * @since       1.0
 */
class PageSpeedChannelHelper extends ChannelHelper
{
    protected $pagespeedClient;

    protected $pagespeed;

    protected $api_key;

    protected $is_auth;

    /**
     * ChannelHelper.
     *
     * @param object     $channel params
     * @param mixed|null $apiKey
     */
    public function __construct($channel, $apiKey = null)
    {
        parent::__construct($channel);

        if ($channel->id) {
            $this->api_key = $this->channel->params->get('api_key');
        }

        if ($apiKey) {
            $this->api_key = $apiKey;
        }
    }

    /**
     * isAuth().
     *
     * @return bool
     */
    public function isAuth()
    {
        $ch = XTF0FTable::getAnInstance('Channel', 'AutoTweetTable');

        if (empty($this->api_key)) {
            $this->api_key = null;

            return false;
        }

        try {
            $this->getApiInstance();
            $url = RouteHelp::getInstance()->getRoot();
            $result = $this->pagespeed->pagespeedapi->runpagespeed($url);

            return true;
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
        $result = [false, 'PageSpeed Unknown', null];

        try {
            $url = $data->org_url;

            $this->getApiInstance();
            $result = $this->pagespeed->pagespeedapi->runpagespeed($url);
            $content = '<pre>'.json_encode($result, \JSON_PRETTY_PRINT).'</pre>';
            $content = str_replace('Extly_', '', $content);

            return $this->sendMail($content, $result);
        } catch (Exception $e) {
            return [
                false,
                $e->getMessage(),
            ];
        }

        return $result;
    }

    /**
     * Internal service functions.
     *
     * @return object
     */
    protected function getApiInstance()
    {
        if (!$this->pagespeedClient) {
            $this->pagespeedClient = new XTS_Google_Client();
            $this->pagespeedClient->setDeveloperKey($this->api_key);
            $this->pagespeed = new XTS_Google_Service_Pagespeedonline($this->pagespeedClient);
        }

        return $this->pagespeedClient;
    }

    /**
     * sendMail.
     *
     * @param string $mailBody Param
     * @param string $result   Param
     *
     * @return bool
     */
    private function sendMail($mailBody, $result)
    {
        AutotweetLogger::getInstance()->log(\Joomla\CMS\Log\Log::INFO, 'sendMail', $message);

        $recipient_mail = $this->get('mail_recipient_email');

        // Prepare email and send try to send it
        $mailSubject = TextUtil::truncString('PageSpeed: '.$result->title, MailChannelHelper::MAX_CHARS_SUBJECT);

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
