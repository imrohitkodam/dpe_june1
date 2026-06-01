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

// Hashtags
$attrs = [
    'ng-model' => 'messageCtrl.hashtags_value',
    'ng-change' => 'messageCtrl.countRemaining()',
];
echo EHtml::textControl(null, 'hashtags', 'COM_AUTOTWEET_VIEW_ITEMEDITOR_HASHTAGS', 'COM_AUTOTWEET_VIEW_ITEMEDITOR_HASHTAGS_DESC', 'hashtags', 128, $attrs);
