<?php

/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

?>
<div id="advancedopts" class="tab-pane fade">
<?php
    echo EHtml::readonlyTextControl(
    ($this->item->enabled ? JText::_('JPUBLISHED') : JText::_('JUNPUBLISHED')),
    'readonlyenabled',
    'JSTATUS',
    'JSTATUS_DESC'
);
    echo '<input type="hidden" name="enabled" value="'.$this->item->enabled.'">';

    if (!$this->item->enabled) {
        echo '<p class="text-error">'.JText::_('COM_AUTOTWEET_VIEW_MANAGER_PLUGIN_DISABLED_ERROR').'</p>';
    }

    echo EHtml::requiredTextControl(
        $this->item->xtform->get('interval', 180),
        'xtform[interval]',
        'COM_AUTOTWEET_VIEW_MANAGER_INTERVAL_LABEL',
        'COM_AUTOTWEET_VIEW_MANAGER_INTERVAL_DESC'
    );

    echo EHtml::idControl(
        $this->item->extension_id,
        'extension_id'
    );

?>
</div>

