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
<h2>
<?php echo JText::_('COM_AUTOTWEET_VIEW_FEED_TAB_TXT_FLTRS'); ?>
</h2>
<?php

    echo '<div class="text_filter">';
    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('text_filter', 0),
        'xtform[text_filter]',
        'COM_AUTOTWEET_VIEW_FEED_TEXT_FILTERING',
        'COM_AUTOTWEET_VIEW_FEED_TEXT_FILTERING_DESC',
        'xtformsave_text_filter'
    );
    echo '</div>';

    // Text Filter Options - BEGIN
    echo '<div class="group-text_filter well">';
    echo EHtml::textControl(
        $this->item->xtform->get('text_filter_remove'),
        'xtform[text_filter_remove]',
        'COM_AUTOTWEET_VIEW_FEED_TEXT_FLTR_RMV',
        'COM_AUTOTWEET_VIEW_FEED_TEXT_FLTR_RMV_DESC'
    );

    echo EHtml::textareaControl(
        $this->item->xtform->get('text_filter_replace'),
        'xtform[text_filter_replace]',
        'COM_AUTOTWEET_VIEW_FEED_TEXT_FLTR_RPLC',
        'COM_AUTOTWEET_VIEW_FEED_TEXT_FLTR_RPLC_DESC'
    );

    echo EHtml::textareaControl(
        $this->item->xtform->get('text_filter_regex'),
        'xtform[text_filter_regex]',
        'COM_AUTOTWEET_VIEW_FEED_TEXT_FLTR_RGX',
        'COM_AUTOTWEET_VIEW_FEED_TEXT_FLTR_RGX_DESC'
    );
    echo '</div>';

    // Text Filter Options - END
