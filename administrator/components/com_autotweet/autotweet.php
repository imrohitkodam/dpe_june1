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

// Check for PHP4
if (defined('PHP_VERSION')) {
    $version = \PHP_VERSION;
} elseif (function_exists('phpversion')) {
    $version = \PHP_VERSION;
} else {
    // No version info. I'll lie and hope for the best.
    $version = '5.0.0';
}

// Old PHP version detected. EJECT! EJECT! EJECT!
if (!version_compare($version, '7.2.0', '>=')) {
    return JError::raise(
        \E_ERROR,
        500,
        'PHP versions 4.x and 5.x are no longer supported by Perfect Publisher.',
        'The version of PHP used on your site is obsolete and contains known security vulenrabilities.
			Moreover, it is missing features required by PerfectPublisher to work properly or at all.
			Please ask your host to upgrade your server to the latest PHP stable release. Thank you!'
    );
}

require_once JPATH_ADMINISTRATOR.'/components/com_autotweet/api/autotweetapi.php';

$base_url = EParameter::getComponentParam(CAUTOTWEETNG, 'base_url');

if ((defined('AUTOTWEET_CRONJOB_RUNNING')) && (AUTOTWEET_CRONJOB_RUNNING) && (!filter_var($base_url, \FILTER_VALIDATE_URL))) {
    throw new Exception('AUTOTWEET_CRONJOB: Url base not set.');
}

$config = [];

$controller = null;

// If we are processing Google Auth, redirect to controller
$session = \Joomla\CMS\Factory::getSession();
$channelId = $session->get('channelId');

if (!empty($channelId)) {
    $input = new \Joomla\CMS\Input\Input($_REQUEST);

    // Google Auth or other OAuth service
    $code = $input->getString('code');

    // ScoopIt
    $oauth_token = $input->getString('oauth_token');
    $oauth_verifier = $input->getString('oauth_verifier');

    // LinkedIn
    $state = $input->getString('state');

    // ScoopIt
    if (((!empty($oauth_token)) && (!empty($oauth_verifier)))
        // LinkedIn
        || ((!empty($code)) && (!empty($state)))
        // Google Auth
        || (!empty($code))) {
        $controller = XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel')->getAuthCallback($channelId);
        $config['input'] = ['task' => 'callback'];
    } else {
        // LinkedIn
        $errorDescription = $input->getString('error_description');

        if ($errorDescription) {
            \Joomla\CMS\Factory::getApplication()->enqueueMessage($errorDescription, 'error');
        }

        $session->set('channelId', false);
    }
}

// XTF0F app
XTF0FDispatcher::getTmpInstance('com_autotweet', $controller, $config)->dispatch();
