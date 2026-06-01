<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

/*
 * @package     Extly Infrastructure Support
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2021 Extly, CB. All rights reserved.
 * @license     http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @see         https://www.extly.com
 */

namespace XTP_BUILD\Extly\Infrastructure\Support;

use XTP_BUILD\Extly\Infrastructure\Support\HttpClient\Helper as HttpClientHelper;

class TimestampHelper
{
    /**
     * get.
     *
     * @return int
     */
    public static function get()
    {
        $response = HttpClientHelper::create()
            ->rawHttpGet('https://api.twitter.com/1.1/account/verify_credentials.json');
        $date = $response->getHeader('date');
        $date = array_shift($date);

        return Date::parse($date)->timestamp;
    }

    /**
     * get.
     *
     * @return int
     */
    private static function getTimeapi()
    {
        $response = HttpClientHelper::create()->get('http://www.timeapi.org/utc/now.json');
        $body = (string) $response->getBody();
        $data = HString::create($body)->decodeJson();

        if ((!$data) || (!isset($data['dateString']))) {
            throw new SupportException('TimestampHelper: Invalid response');
        }

        return strtotime($data['dateString']);
    }
}
