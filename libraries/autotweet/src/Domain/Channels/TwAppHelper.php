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
 * TwAppHelper class.
 *
 * @since       1.0
 */
class TwAppHelper
{
    protected $consumer_key;

    protected $consumer_secret;

    protected $access_token;

    protected $access_token_secret;
    private $twitter;

    /**
     * TwAppHelper.
     *
     * @param string $consumer_key        params
     * @param string $consumer_secret     params
     * @param string $access_token        params
     * @param string $access_token_secret params
     */
    public function __construct($consumer_key = '', $consumer_secret = '', $access_token = null, $access_token_secret = null)
    {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
        $this->access_token = $access_token;
        $this->access_token_secret = $access_token_secret;
    }

    /**
     * login.
     *
     * @return object
     */
    public function login()
    {
        if (!$this->twitter) {
            $this->twitter = new \XTS_BUILD\Abraham\TwitterOAuth\TwitterOAuth(
                $this->consumer_key,
                $this->consumer_secret,
                $this->access_token,
                $this->access_token_secret
            );
            // $this->twitter->setApiVersion('2');
        }

        return $this->twitter;
    }

    /**
     * getApi.
     *
     * @return object
     */
    public function getApi()
    {
        return $this->twitter;
    }

    /**
     * verify.
     *
     * @return bool
     */
    public function verify()
    {
        $connection = $this->login();
        $response = $connection->get('account/verify_credentials');

        if (200 === (int) $connection->getLastHttpCode()) {
            $user = $response;
            $url = 'https://twitter.com/'.$user->screen_name;

            $message = [
                'status' => true,
                'error_message' => null,
                'user' => $user,
                'url' => $url,
            ];
        } else {
            if (isset($response->errors)) {
                $error = array_shift($response->errors);
                $message = $error->code.' - '.$error->message;
            } else {
                $message = 'Error '.$connection->getLastHttpCode();
            }

            $message = [
                'status' => false,
                'error_message' => $message,
            ];
        }

        return $message;
    }

    /**
     * checkTimestamp.
     *
     * @return bool
     */
    public static function checkTimestamp()
    {
        // Get component parameter - Offline mode
        $version_check = EParameter::getComponentParam(CAUTOTWEETNG, 'version_check', 1);

        if (!$version_check) {
            return '998 Offline';
        }

        $appHelper = new self();
        $appHelper->verify();
        $connection = $appHelper->getApi();
        $response = $connection->response;

        $dateCompare = '999 Unable to check';

        if ((isset($connection->response->headers))
            && (array_key_exists('date', $connection->response->headers))) {
            $headers = $connection->response->headers;
            $twitterDate = $headers['date'];
            $twistamp = strtotime($twitterDate);
            $srvstamp = time();
            $dateCompare = abs($srvstamp - $twistamp);
        }

        return $dateCompare;
    }

    /**
     * getUserTimeline.
     *
     * @param string $twUsername  Param
     * @param int    $twMaxTweets Param
     *
     * @return array
     */
    public function getUserTimeline($twUsername, $twMaxTweets)
    {
        $connection = $this->login();
        $response = $connection->get(
            'statuses/user_timeline',
            [
                'screen_name' => $twUsername,
                'count' => $twMaxTweets,
            ]
        );

        return $response;
    }

    /**
     * Obtain a request token from Twitter.
     *
     * @return string
     */
    public function getAccessToken()
    {
        $session = \Joomla\CMS\Factory::getSession();

        $input = new \Joomla\CMS\Input\Input($_REQUEST);
        $oauth_token = $input->get('oauth_token');
        $oauth_verifier = $input->get('oauth_verifier');

        $this->access_token = $oauth_token;
        $this->token_secret = $oauth_verifier;
        $this->login();

        $logger = AutotweetLogger::getInstance();
        $logger->log(\Joomla\CMS\Log\Log::INFO, 'getAccessToken - oauth_verifier: '.$oauth_verifier);

        // Send request for an access token
        $connection = $this->twitter;
        $response = $connection->oauth(
            'oauth/access_token',
            [
                // Pass the oauth_verifier received from Twitter
                'oauth_token' => $oauth_token,
                'oauth_verifier' => $oauth_verifier,
            ]
        );

        if (200 === (int) $connection->getLastHttpCode()) {
            $access_token = $response['oauth_token'];
            $access_token_secret = $response['oauth_token_secret'];

            return [
                'access_token' => $access_token,
                'access_token_secret' => $access_token_secret,
            ];
        }

        if (isset($response->errors)) {
            $error = array_shift($response->errors);
            $message = $error->code.' - '.$error->message;
        } else {
            $message = 'Error '.$connection->getLastHttpCode();
        }

        throw new Exception($message);

        return false;
    }
}
