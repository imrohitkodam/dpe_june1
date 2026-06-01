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
$options[] = [
    'name' => '<i class="xticon far fa-circle"></i> '.JText::_('COM_AUTOTWEET_DEFAULT_LABEL'),
    'value' => PostShareManager::POSTTHIS_DEFAULT,
];
$options[] = [
    'name' => '<i class="xticon fas fa-check"></i> '.JText::_('JYES'),
    'value' => PostShareManager::POSTTHIS_YES,
];
$options[] = [
    'name' => '<i class="xticon fas fa-dice-one"></i> '.JText::_('COM_AUTOTWEET_POSTTHIS_ONLYONCE'),
    'value' => PostShareManager::POSTTHIS_ONLYONCE,
];
$options[] = [
    'name' => '<i class="xticon fas fa-bolt"></i> '.JText::_('COM_AUTOTWEET_POSTTHIS_IMMEDIATELY'),
    'value' => PostShareManager::POSTTHIS_IMMEDIATELY,
];
$options[] = [
    'name' => '<i class="xticon fas fa-times"></i> '.JText::_('JNO'),
    'value' => PostShareManager::POSTTHIS_NO,
];

$attrs = [
    'ng-model' => 'editorCtrl.itemeditor_postthis_value',
];

echo EHtmlSelect::ngBtnGroupListControl(
    PostShareManager::POSTTHIS_DEFAULT,
    'postThis',
    'COM_AUTOTWEET_VIEW_ITEMEDITOR_POSTTHIS',
    'COM_AUTOTWEET_VIEW_ITEMEDITOR_POSTTHIS_DESC',
    $options,
    'itemeditor_postthis',
    $attrs
);
