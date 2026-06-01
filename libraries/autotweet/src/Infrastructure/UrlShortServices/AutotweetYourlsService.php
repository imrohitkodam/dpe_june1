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
 * AutotweetYourlsService.
 *
 * @since       1.0
 */
class AutotweetYourlsService extends AutotweetShortservice
{
    /**
     * getShortURL.
     *
     * @param string $long_url param
     *
     * @return string
     */
    public function getShortUrl($long_url)
    {
        // Create the data to be encoded into JSON
        $requestData = [
            'url' => $long_url,
            'format' => 'json',
            'action' => 'shorturl',
            'signature' => $this->data['yourls_token'],
        ];

        // curl -D headers.log -F "url=https://www.extly.com" -F format=json -F action=shorturl -F
        //    signature=..... https://ppub.link/yourls-api.php
        $result = $this->callPostService($this->data['yourls_host'], $requestData);

        $result_code = $result[0];
        $output = $result[1];

        if ((200 !== (int) $result_code) || (!isset($output->shorturl))) {
            $short_url = null;
            $this->error_msg = '('.$result_code.')'
                    .' / '.$output->status
                    .' - '.$output->code;
        } else {
            $short_url = $output->shorturl;
        }

        if (($short_url) && (!RouteHelp::getInstance()->validateUrl($short_url))) {
            $short_url = null;
            $this->error_msg = JText::sprintf(COM_AUTOTWEET_ERR_INVALID_SHORTURL, $short_url);
        }

        return $short_url;
    }
}
