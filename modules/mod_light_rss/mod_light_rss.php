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
use Joomla\CMS\Helper\ModuleHelper;

if (!defined('AUTOTWEET_API') && !@include_once(JPATH_ADMINISTRATOR.'/components/com_autotweet/api/autotweetapi.php')) {
    return;
}

// Include the helper functions only once
require_once __DIR__.'/helper.php';

$enable_tooltip = ('yes' === $params->get('enable_tooltip', 'yes'));

// Get data from helper class
$light_rss = modLightRSSHelper::getFeed($params);

$rssrtl = false;

// Run default template script for output
require ModuleHelper::getLayoutPath('mod_light_rss');
