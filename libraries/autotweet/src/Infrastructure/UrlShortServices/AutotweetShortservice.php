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

// Base class for AutoTweet url short services.

/**
 * AutotweetShortservice.
 *
 * @since       1.0
 */
abstract class AutotweetShortservice
{
    // Seconds
    public const CXN_TIMEOUT = 5;

    // Seconds
    public const EXEC_TIMEOUT = 10;

    protected $data;

    protected $error_msg;

    /**
     * AutotweetShortservice.
     *
     * @param array $data Param
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * getShortURL.
     *
     * @param string $long_url param
     *
     * @return string
     */
    abstract public function getShortUrl($long_url);

    /**
     * getErrorMessage.
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->error_msg;
    }

    /**
     * callSimpleService.
     *
     * @param string $service_url param
     * @param string $long_url    param
     *
     * @return string
     */
    protected function callSimpleService($service_url, $long_url)
    {
        $enc_url = urlencode($long_url);
        $service_call = $service_url.$enc_url;

        $c = curl_init();
        curl_setopt($c, \CURLOPT_URL, $service_call);
        curl_setopt($c, \CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, \CURLOPT_CONNECTTIMEOUT, self::CXN_TIMEOUT);
        curl_setopt($c, \CURLOPT_TIMEOUT, self::EXEC_TIMEOUT);

        $result = curl_exec($c);
        $result_code = curl_getinfo($c);
        curl_close($c);

        if (200 !== (int) $result_code['http_code']) {
            $short_url = null;
            $this->error_msg = 'Code:'.$result_code['http_code'];
        } else {
            $short_url = $result;
        }

        if (($short_url) && (!RouteHelp::getInstance()->validateUrl($short_url))) {
            $short_url = null;
            $this->error_msg = JText::sprintf(COM_AUTOTWEET_ERR_INVALID_SHORTURL, $short_url);
        }

        return $short_url;
    }

    /**
     * callComplexService.
     *
     * @param string $service_call param
     *
     * @return array
     */
    protected function callComplexService($service_call)
    {
        $c = curl_init();
        curl_setopt($c, \CURLOPT_URL, $service_call);
        curl_setopt($c, \CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, \CURLOPT_CONNECTTIMEOUT, self::CXN_TIMEOUT);
        curl_setopt($c, \CURLOPT_TIMEOUT, self::EXEC_TIMEOUT);

        $result = curl_exec($c);
        $result_code = curl_getinfo($c);
        curl_close($c);

        return [(int) $result_code['http_code'], json_decode($result)];
    }

    /**
     * callJsonService.
     *
     * @param string $service_call param
     * @param string $requestData  param
     *
     * @return array
     */
    protected function callJsonService($service_call, $requestData)
    {
        // Initialize the cURL connection
        $ch = curl_init($service_call);

        // Tell cURL to return the data rather than outputting it
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);

        // Change the request type to POST
        curl_setopt($ch, \CURLOPT_POST, true);

        // Set the form content type for JSON data
        curl_setopt($ch, \CURLOPT_HTTPHEADER, ['Content-type: application/json']);

        // Set the post body to encoded JSON data
        curl_setopt($ch, \CURLOPT_POSTFIELDS, json_encode($requestData));

        curl_setopt($ch, \CURLOPT_CONNECTTIMEOUT, self::CXN_TIMEOUT);
        curl_setopt($ch, \CURLOPT_TIMEOUT, self::EXEC_TIMEOUT);

        // Perform the request
        $result = curl_exec($ch);
        $result_code = curl_getinfo($ch);
        curl_close($ch);

        return [(int) $result_code['http_code'], json_decode($result)];
    }

    /**
     * callPostService.
     *
     * @param string $service_call param
     * @param mixed  &$requestData Param
     *
     * @return array
     */
    protected function callPostService($service_call, &$requestData)
    {
        // Initialize the cURL connection
        $ch = curl_init();
        curl_setopt($ch, \CURLOPT_URL, $service_call);

        // No header in the result
        curl_setopt($ch, \CURLOPT_HEADER, 0);

        // Return, do not echo result
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);

        // This is a POST request
        curl_setopt($ch, \CURLOPT_POST, true);

        // Data to POST
        curl_setopt($ch, \CURLOPT_POSTFIELDS, $requestData);

        curl_setopt($ch, \CURLOPT_CONNECTTIMEOUT, self::CXN_TIMEOUT);
        curl_setopt($ch, \CURLOPT_TIMEOUT, self::EXEC_TIMEOUT);

        // Fetch and return content
        $result = curl_exec($ch);
        $result_code = curl_getinfo($ch);
        curl_close($ch);

        return [(int) $result_code['http_code'], json_decode($result)];
    }
}
