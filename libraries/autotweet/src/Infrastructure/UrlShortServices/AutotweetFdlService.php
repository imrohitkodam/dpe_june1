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
 * AutotweetFdlService.
 *
 * @since       1.0
 */
class AutotweetFdlService extends AutotweetShortservice
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
            $googleApiKey = EParameter::getComponentParam(CAUTOTWEETNG, 'fdl_google_api_key');
            $fdlDomain = EParameter::getComponentParam(CAUTOTWEETNG, 'fdl_domain');

            $serviceUrl = 'https://firebasedynamiclinks.googleapis.com/v1/shortLinks?key='.$googleApiKey;
            $packet = [
                'longDynamicLink' => 'https://'.$fdlDomain.'/?link='.urlencode($longUrl),
                'suffix' => ['option' => 'SHORT'],
            ];
            $response = $this->callJsonService($serviceUrl, $packet);

            if (((is_array($response)) && (2 === count($response))) && (200 === (int) $response[0])) {
                $shortUrl = $response[1]->shortLink;
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
