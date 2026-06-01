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

if (defined('AUTOTWEET_API')) {
    return;
}

define('AUTOTWEET_API', true);

defined('CAUTOTWEETNG') || define('CAUTOTWEETNG', 'com_autotweet');
defined('CAUTOTWEETNG_VERSION') || define('CAUTOTWEETNG_VERSION', '9.6.1');

defined('JPATH_AUTOTWEET') || define('JPATH_AUTOTWEET', JPATH_ADMINISTRATOR.'/components/com_autotweet');
defined('JPATH_AUTOTWEET_LAYOUTS') || define('JPATH_AUTOTWEET_LAYOUTS', JPATH_AUTOTWEET.'/layouts');
defined('JPATH_AUTOTWEET_VENDOR') || define('JPATH_AUTOTWEET_VENDOR', JPATH_LIBRARIES.'/autotweet/vendor');
defined('JPATH_XTPLATFORM_VENDOR') || define('JPATH_XTPLATFORM_VENDOR', JPATH_LIBRARIES.'/xtplatform2/vendor');

defined('JPATH_JOOCIAL_APP_MEDIA') || define('JPATH_JOOCIAL_APP_MEDIA', JPATH_ROOT.\DIRECTORY_SEPARATOR.'media/com_autotweet/composer');

if (!class_exists('XTP_BUILD\Extly\Infrastructure\Support\SupportException') && !@include_once(JPATH_XTPLATFORM_VENDOR.'/autoload.php')) {
    return;
}

if (!class_exists('AutotweetLogger') && !@include_once(JPATH_AUTOTWEET_VENDOR.'/autoload.php')) {
    return;
}

Extlyframework::initialize();

require_once 'VersionHelper.php';
VersionHelper::initialize();

// Compatibility with Legacy Plugins
@class_alias('PlgAutotweetBase', 'plgAutotweetBase');
