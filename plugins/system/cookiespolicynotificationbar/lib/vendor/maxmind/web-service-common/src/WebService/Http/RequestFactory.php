<?php
/* ======================================================
# Cookies Policy Notification Bar for Joomla! - v4.0.0 (Pro version)
# -------------------------------------------------------
# For Joomla! CMS
# Author: Web357 (Yiannis Christodoulou)
# Copyright (©) 2009-2020 Web357. All rights reserved.
# License: GNU/GPLv3, http://www.gnu.org/licenses/gpl-3.0.html
# Website: https:/www.web357.com/
# Demo: https://demo.web357.com/joomla/cookiespolicynotificationbar
# Support: support@web357.com
# Last modified: 26 Nov 2020, 01:41:47
========================================================= */

namespace MaxMind\WebService\Http;

/**
 * Class RequestFactory.
 *
 * @internal
 */
class RequestFactory
{
    public function __construct()
    {
    }

    /**
     * @param $url
     * @param $options
     *
     * @return CurlRequest
     */
    public function request($url, $options)
    {
        return new CurlRequest($url, $options);
    }
}
