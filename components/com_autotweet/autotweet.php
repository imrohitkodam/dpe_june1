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

$config = [];
$view = null;

// XTF0F app
XTF0FDispatcher::getTmpInstance('com_autotweet', $view, $config)->dispatch();
