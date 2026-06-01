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
 * AutotweetTinyurlcomService - AutoTweet tinyurl.com url short service.
 *
 * @since       1.0
 */
class AutotweetTinyurlcomService extends AutotweetShortservice
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
        return $this->callSimpleService('http://tinyurl.com/api-create.php?url=', $long_url);
    }
}
