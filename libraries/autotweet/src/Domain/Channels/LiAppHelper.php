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
 * LiAppHelper class.
 *
 * @since       1.0
 */
class LiAppHelper
{
    protected $api_key;

    protected $secret_key;

    protected $oauth_user_token;

    protected $oauth_user_secret;
    private $linkedin;

    /**
     * LiAppHelper.
     *
     * @param string $api_key           params
     * @param string $secret_key        params
     * @param string $oauth_user_token  params
     * @param string $oauth_user_secret params
     */
    public function __construct($api_key, $secret_key, $oauth_user_token = null, $oauth_user_secret = null)
    {
        $this->api_key = $api_key;
        $this->secret_key = $secret_key;
        $this->oauth_user_token = $oauth_user_token;
        $this->oauth_user_secret = $oauth_user_secret;
    }

    /**
     * login.
     *
     * @return object
     */
    public function login()
    {
        if (!$this->linkedin) {
            $this->linkedin = new \XTS_BUILD\Happyr\LinkedIn\LinkedIn(
                $this->api_key,
                $this->secret_key
            );

            if ($this->oauth_user_token) {
                $accessToken = [
                    'oauth_token' => $this->oauth_user_token,
                    'oauth_token_secret' => $this->oauth_user_secret,
                ];

                $this->linkedin->setTokenAccess($accessToken);
            }
        }

        return $this->linkedin;
    }

    /**
     * getUser.
     *
     * @return object
     */
    public function getUser()
    {
        if (empty($this->api_key)
            || empty($this->secret_key)
            || empty($this->oauth_user_token)
            || empty($this->oauth_user_secret)) {
            return [false, 'Access Token and/or Token secret not entered (getUser).'];
        }

        $result = null;

        try {
            $api = $this->login();
            $response = $api->api('GET', '/v1/people/~:(id,firstName,lastName,headline,publicProfileUrl)');

            if (isset($response['id'])) {
                $user = json_decode(json_encode($response));
                $url = $user->publicProfileUrl;

                $result = [
                    'status' => true,
                    'error_message' => 'Ok!',
                    'user' => $user,
                    'url' => $url,
                ];
            } else {
                $errorMessage = $response['message'].' '.$response['status'];

                $result = [
                    'status' => false,
                    'error_message' => $errorMessage,
                ];
            }
        } catch (Exception $e) {
            $result = [
                'status' => false,
                'error_message' => $e->getMessage(),
            ];
        }

        return $result;
    }

    /**
     * getMyGroup.
     *
     * @return object
     */
    public function getMyGroups()
    {
        if (empty($this->api_key)
            || empty($this->secret_key)
            || empty($this->oauth_user_token)
            || empty($this->oauth_user_secret)) {
            return [false, 'Access Token and/or Token secret not entered (getMyGroup).'];
        }

        $result = null;

        try {
            $api = $this->login();
            $response = $api->groupXTDOwnerships();

            if ((bool) $response['success']) {
                $xml = $response['linkedin'];
                $groups = simplexml_load_string($xml);
                $groups = json_decode(json_encode($groups));

                $result = [];

                if (isset($groups->{'group-membership'})) {
                    $results = $groups->{'group-membership'};

                    if (is_array($results)) {
                        foreach ($results as $group) {
                            $g = $group->group;
                            $g->url = 'https://www.linkedin.com/groups?home=&gid='.$g->id;

                            $result[] = $g;
                        }
                    } elseif (is_object($results)) {
                        $g = $results->group;
                        $g->url = 'https://www.linkedin.com/groups?home=&gid='.$g->id;

                        $result[] = $g;
                    }
                }
            } else {
                $msg = $response['info']['http_code'].' '.JText::_('COM_AUTOTWEET_HTTP_ERR_'.$response['info']['http_code']);
                $result = [false, $msg];

                return $result;
            }

            $response = $api->groupXTDMemberships();

            if ((bool) $response['success']) {
                $xml = $response['linkedin'];
                $groups = simplexml_load_string($xml);
                $groups = json_decode(json_encode($groups));

                if (isset($groups->{'group-membership'})) {
                    $results = $groups->{'group-membership'};

                    if (is_array($results)) {
                        foreach ($results as $group) {
                            $g = $group->group;
                            $g->url = 'https://www.linkedin.com/groups?home=&gid='.$g->id;

                            $result[] = $g;
                        }
                    } elseif (is_object($results)) {
                        $g = $results->group;
                        $g->url = 'https://www.linkedin.com/groups?home=&gid='.$g->id;

                        $result[] = $g;
                    }
                }
            }
        } catch (LinkedInException $e) {
            $result = ['id' => false, 'name' => $e->getMessage()];
        }

        return $result;
    }

    /**
     * getMyCompanies.
     *
     * @return object
     */
    public function getMyCompanies()
    {
        if (empty($this->api_key)
            || empty($this->secret_key)
            || empty($this->oauth_user_token)
            || empty($this->oauth_user_secret)) {
            return [false, 'Access Token and/or Token secret not entered (getMyCompanies).'];
        }

        $result = null;

        try {
            $api = $this->login();
            $response = $api->company('?is-company-admin=true');

            if ((bool) $response['success']) {
                $xml = $response['linkedin'];
                $companies = simplexml_load_string($xml);
                $companies = json_decode(json_encode($companies));

                $result = [];

                // One or more companies
                if (isset($companies->company)) {
                    $result = $companies->company;

                    // We have an array
                    if (is_array($result)) {
                        // Building Urls
                        $companies = [];

                        foreach ($result as $c) {
                            $url = 'https://www.linkedin.com/company/'.$c->id;
                            $c->url = $url;
                            $companies[] = $c;
                        }

                        return $result;
                    }

                    // One Company
                    // It's an object wrapped in an array

                    $url = 'https://www.linkedin.com/company/'.$result->id;
                    $result->url = $url;

                    return [$result];
                }
            } else {
                $msg = $response['info']['http_code'].' '.JText::_('COM_AUTOTWEET_HTTP_ERR_'.$response['info']['http_code']);
                $result = [false, $msg];
            }
        } catch (LinkedInException $e) {
            $result = ['id' => false, 'name' => $e->getMessage()];
        }

        return $result;
    }

    /**
     * Obtain a request token from Twitter.
     *
     * @return string
     */
    public function getAccessToken()
    {
        $session = \Joomla\CMS\Factory::getSession();

        // Set the request token and secret we have stored
        $oauth_token = $session->get('oauth_token');
        $oauth_token_secret = $session->get('oauth_token_secret');

        $this->oauth_user_token = $oauth_token;
        $this->oauth_user_secret = $oauth_token_secret;
        $this->login();

        $input = new \Joomla\CMS\Input\Input($_REQUEST);
        $oauth_verifier = $input->get('oauth_verifier');

        // Send request for an access token
        $response = $this->linkedin->retrieveTokenAccess($oauth_token, $oauth_token_secret, $oauth_verifier);

        if (200 === (int) $response['info']['http_code']) {
            $oauth_user_token = $response['linkedin']['oauth_token'];
            $oauth_user_secret = $response['linkedin']['oauth_token_secret'];

            return [
                'oauth_user_token' => $oauth_user_token,
                'oauth_user_secret' => $oauth_user_secret,
            ];
        }

        return false;
    }
}
