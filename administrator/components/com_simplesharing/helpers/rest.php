<?php

/**
 * @version     1.0.2
 * @package     com_simplesharing
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @author      NYC HelpDesk.co LLC <support@nychelpdesk.co> - nychelpdesk.co
 */
// No direct access
defined('_JEXEC') or die;

/**
 * Simplesharing helper.
 */
class SimplesharingRestHelper {

    public static function getRestCredentials($website) {
        $creds = array();

        // Setting the headers for REST
        $username = $website->admin_username;
        $password = $website->admin_password;
        $token = $website->token;

        // Setting the headers for REST
        $str = $username . ":" . $password;
        $creds['SSH_AUTH'] = base64_encode($str);

        // Encoding user
        $user_encode = $username . ":" . $token;
        $creds['SSH_USER'] = base64_encode($user_encode);
        // Sending by other way, some servers not allow AUTH_ values
        //$creds['USER'] = base64_encode($user_encode);
        // Encoding password
        $pw_encode = $password . ":" . $token;
        $creds['SSH_PW'] = base64_encode($pw_encode);
        // Sending by other way, some servers not allow AUTH_ values
        //$creds['PW'] = base64_encode($pw_encode);
        // Encoding key
        $key_encode = $token . ":" . $token;
        $creds['SSH_KEY'] = base64_encode($key_encode);

        return $creds;
    }

    public static function processRestRequest($website, $task = 'getCategories', $data = null) {

        $responseCodes = Array(
            402 => 'Client key do not match.',
            405 => 'Username headers not found.',
            403 => 'Username not found.',
            406 => 'Username or password do not match.',
            401 => 'Username is not Super Administrator.',
            400 => 'Invalid password, authorization failed',
            407 => 'Failed to process the request, check the log file.'
        );
        $http = JHttpFactory::getHttp();
        $creds = self::getRestCredentials($website);
        $creds['sshtask'] = $task;
        if (is_object($data)) {
            $data = json_decode(json_encode($data, JSON_HEX_QUOT | JSON_HEX_TAG), true);
        }
        $data['debug_mode'] = $website->debug_mode;
        $data['simpleshare'] = 1;
        $data = array_merge($data, $creds);

        $url = $website->website_url . '/index.php';
        $request = $http->post($url, $data);

        $code = $request->code;

        if ($code == 500) {
            throw new Exception('COM_SIMPLESHARING_ERROR_REST_REQUEST');
        } else {
            if ($code != 200 && $code != 301) {
                $request->body = $responseCodes[$code];                
            }
            return $request;
        }
    }

}
