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
 * AutotweetGooglService.
 *
 * @since       1.0
 */
class AutotweetGooglService extends AutotweetShortservice
{
    /**
     * getShortURL.
     *
     * @param string $longUrl param
     *
     * @return string
     */
    public function getShortUrl($longUrl)
    {
        try {
            $shortUrl = null;
            $googleApiKey = EParameter::getComponentParam(CAUTOTWEETNG, 'google_api_key');

            $client = new XTS_Google_Client();
            $client->setDeveloperKey($googleApiKey);
            $service = new XTS_Google_Service_Urlshortener($client);

            $url = new XTS_Google_Service_Urlshortener_Url();
            $url->longUrl = $longUrl;

            $response = $service->url->insert($url);

            if (isset($response->id)) {
                $shortUrl = $response->id;
            }
        } catch (Exception $e) {
            $this->error_msg = $e->getMessage();
        }

        if (($shortUrl) && (!RouteHelp::getInstance()->validateUrl($shortUrl))) {
            $shortUrl = null;
            $this->error_msg = JText::sprintf(COM_AUTOTWEET_ERR_INVALID_SHORTURL, $shortUrl);
        }

        return $shortUrl;
    }
}
