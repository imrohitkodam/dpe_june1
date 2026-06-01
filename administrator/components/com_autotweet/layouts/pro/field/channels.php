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

$control = SelectControlHelper::channels(
    (isset($this->item->xtform) ? $this->item->xtform->get('channelchooser') : null),
    'channelchooser[]',
    [
        'multiple' => true,
        'class' => 'xt-editor__channelchooser no-chosen',
        'data-placeholder' => '-'.JText::_('JSELECT').'-',
        'ng-model' => $displayData['controller'].'.channelchooser_value',
    ],
    'channelchooser'
);

echo EHtml::genericControl(
    'COM_AUTOTWEET_VIEW_ITEMEDITOR_CHANNELS',
    'COM_AUTOTWEET_VIEW_ITEMEDITOR_CHANNELS_DESC',
    'channelchooser',
    $control
);
