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

// FacebookApp to authorize and grant permissions for the AutoTweet standard App or your own App

/**
 * FacebookApp class.
 *
 * @since       1.0
 */
class FacebookApp
{
    public const PERMS_API7 = 'public_profile,pages_manage_posts,pages_read_engagement,publish_to_groups,groups_access_member_info,pages_show_list';

    public const PERMS_API7_PAGES_AND_GROUPS = 'public_profile,pages_manage_posts,pages_read_engagement,publish_to_groups,groups_access_member_info,pages_show_list';

    public const PERMS_API_INSTAGRAM = 'public_profile,pages_manage_posts,pages_read_engagement,publish_to_groups,groups_access_member_info,instagram_basic,instagram_content_publish,pages_show_list';

    public $APP_ID;

    public $APP_SECRET;

    public $login_url;

    public $facebook;

    /**
     * Init from request.
     */
    public function init()
    {
        // Called form AutoTweet backend
        $this->APP_ID = filter_input(\INPUT_GET, 'app_id', \FILTER_SANITIZE_STRING);
        $this->APP_SECRET = filter_input(\INPUT_GET, 'secret', \FILTER_SANITIZE_STRING);

        if (((empty($this->APP_ID)) || ('My-App-ID' === $this->APP_ID)) && (defined('MY_APP_ID'))) {
            $this->APP_ID = MY_APP_ID;
        }

        if (((empty($this->APP_SECRET)) || ('My-App-Secret' === $this->APP_SECRET)) && (defined('MY_APP_SECRET'))) {
            $this->APP_SECRET = MY_APP_SECRET;
        }

        if (DEBUG_ENABLED) {
            echo '<div class="xt-alert xt-alert-block alert-error"><!-- Removed button close data-dismiss="alert" -->';
            echo '<h2>Debug information part 1: request data</h2>';
            echo '<p>request: ';
            print_r($_REQUEST);
            echo '</p>';
            echo '<ul>';
            echo '<li>app id: '.$this->APP_ID.'</li>';
            echo '<li>api secret: '.$this->APP_SECRET.'</li>';
            echo '</ul>';
            echo '</div>';
        }

        $ok = !(empty($this->APP_ID) || empty($this->APP_SECRET));

        if ($ok) {
            $this->facebook = new facebookphpsdk\Facebook(
                [
                    'appId' => $this->APP_ID,
                    'secret' => $this->APP_SECRET,
                    'cookie' => true,
                ]
            );
        }

        return $ok;
    }

    /**
     * Login.
     */
    public function login()
    {
        $user = $this->facebook->getUser();

        if (empty($user) || !isset($_REQUEST['state'])) {
            $ref = filter_input(\INPUT_SERVER, 'HTTP_REFERER', \FILTER_SANITIZE_URL);

            if (defined('MY_CANVAS_URL')) {
                $ref = MY_CANVAS_URL;
            }

            if (empty($ref)) {
                echo '<div class="xt-alert xt-alert-block alert-error"><!-- Removed button close data-dismiss="alert" -->';
                echo '<h2>HTTP_REFERER not available.</h2>';
                echo '<ul>';
                echo '<li>Please, enable the HTTP_REFERER or define MY_CANVAS_URL.</li>';
                echo '</ul>';
                echo '</div>';
            }

            $params = [
                'redirect_uri' => $ref,
                'scope' => self::PERMS_API7,
                'state' => $this->APP_ID.','.$this->APP_SECRET.','.$ref,
            ];
            $this->login_url = $this->facebook->getLoginUrl($params);

            if (DEBUG_ENABLED) {
                echo '<div class="xt-alert xt-alert-block alert-error"><!-- Removed button close data-dismiss="alert" -->';
                echo '<h2>Debug information part 2: login data</h2>';
                echo '<ul>';
                echo '<li>user: '.print_r($user, true).'</li>';
                echo '<li>state: '.$_REQUEST['state'].'</li>';
                echo '<li>login_url: '.$this->login_url.'</li>';
                echo '</ul>';
                echo '</div>';
            }

            return false;
        }

        return true;
    }

    /**
     * isFacebookBot.
     *
     * @return bool
     */
    public static function isFacebookBot()
    {
        $httpUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        if ((false !== strpos($httpUserAgent, 'facebookexternalhit/'))) {
            return true;
        }

        if (false !== strpos($httpUserAgent, 'Facebot')) {
            return true;
        }

        return false;
    }
}
