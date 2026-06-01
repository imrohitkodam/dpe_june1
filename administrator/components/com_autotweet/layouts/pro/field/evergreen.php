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

$options = [];
$options[] = ['name' => '<i class="xticon fas fa-check"></i> '.JText::_('JYES'), 'value' => PostShareManager::POSTTHIS_YES];
$options[] = ['name' => '<i class="xticon fas fa-times"></i> '.JText::_('JNO'), 'value' => PostShareManager::POSTTHIS_NO];

$attrs = [
    'ng-model' => 'editorCtrl.itemeditor_evergreen_value',
];

echo EHtmlSelect::ngBtnGroupListControl(
    PostShareManager::POSTTHIS_NO,
    'everGreen',
    'COM_AUTOTWEET_VIEW_ITEMEDITOR_EVERGREEN',
    'COM_AUTOTWEET_VIEW_ITEMEDITOR_EVERGREEN_DESC',
    $options,
    'itemeditor_evergreen',
    $attrs
);
