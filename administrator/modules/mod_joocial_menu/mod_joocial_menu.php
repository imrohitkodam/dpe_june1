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

if (!AutoTweetDefaultView::isEnabledAttrComps()) {
    return;
}

$link = AutoTweetDefaultView::addItemeditorHelperApp();

if (!$link) {
    return;
}

$layout = 'default';

if (EXTLY_J4) {
    $layout = 'default.j4';
}

require ModuleHelper::getLayoutPath('mod_joocial_menu', $layout);
