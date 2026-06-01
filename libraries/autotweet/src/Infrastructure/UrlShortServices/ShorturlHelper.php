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
 * ShorturlHelper.
 *
 * @since       1.0
 */
class ShorturlHelper
{
    // Seconds
    public const RESEND_DELAY = 1;

    // General params for message and posting
    protected $resend_attempts = 2;

    protected $shorturl_service = 'Tinyurlcom';

    // Bit.ly and yourls account data
    protected $bit_access_token = '';

    protected $yourls_host = '';

    protected $yourls_token = '';

    private static $_instance = null;

    /**
     * ShorturlHelper. No public access (singleton pattern).
     */
    protected function __construct()
    {
        // General params for message and posting
        $this->resend_attempts = EParameter::getComponentParam(CAUTOTWEETNG, 'resend_attempts', 2);
        $this->shorturl_service = EParameter::getComponentParam(CAUTOTWEETNG, 'shorturl_service', 'Tinyurlcom');

        // Bit.ly, Goog.gl and yourls account data
        $this->bit_access_token = EParameter::getComponentParam(CAUTOTWEETNG, 'bit_access_token', null);
        $this->yourls_host = EParameter::getComponentParam(CAUTOTWEETNG, 'yourls_host', null);
        $this->yourls_token = EParameter::getComponentParam(CAUTOTWEETNG, 'yourls_token', null);

        // Init AutoTweet logging
        $this->logger = AutotweetLogger::getInstance();
    }

    /**
     * getInstance.
     *
     * @return Instance
     */
    public static function &getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * getShortUrl.
     *
     * @param string $url Param
     *
     * @return string
     */
    public function getShortUrl($url)
    {
        $shorturl_service = $this->shorturl_service;

        if (('0' !== $shorturl_service) && !empty($url)) {
            // Get short url service
            $data = [
                'type' => $shorturl_service,
                'bit_access_token' => $this->bit_access_token,
                'yourls_host' => $this->yourls_host,
                'yourls_token' => $this->yourls_token,
            ];
            $service = AutotweetURLShortserviceFactory::getInstance($data);

            // Get short url
            $attempt = 0;

            do {
                $resend = false;
                ++$attempt;

                $short_url = $service->getShortUrl($url);

                if (($attempt < $this->resend_attempts) && empty($short_url)) {
                    $resend = true;
                    $this->logger->log(\Joomla\CMS\Log\Log::WARNING, 'getShortUrl: Short url service '.$shorturl_service.' '.$service->getErrorMessage().' - try again in '.self::RESEND_DELAY.' seconds');

                    sleep(self::RESEND_DELAY);
                }
            } while ($resend);

            if (!empty($short_url)) {
                $url = $short_url;
                $this->logger->log(\Joomla\CMS\Log\Log::INFO, 'getShortUrl: url shortened, short url = '.$short_url);
            } else {
                $this->logger->log(\Joomla\CMS\Log\Log::WARNING, 'getShortUrl: Short url service '.$shorturl_service.' failed. Normal url used.');
            }
        }

        return $url;
    }
}
