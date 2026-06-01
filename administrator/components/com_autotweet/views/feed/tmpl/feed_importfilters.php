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
<h3>
<?php echo JText::_('COM_AUTOTWEET_VIEW_FEED_TAB_IMPORT_FLTRS'); ?>
</h3>
<?php

    echo '<div class="filtering">';
    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('filtering', 0),
        'xtform[filtering]',
        'COM_AUTOTWEET_VIEW_FEED_FILTERING',
        'COM_AUTOTWEET_VIEW_FEED_FILTERING_DESC',
        'xtformsave_filtering'
    );
    echo '</div>';

    // Import Filter Options - BEGIN
    echo '<div class="group-filtering well">';
    echo EHtml::textControl(
        $this->item->xtform->get('filter_category_term', ''),
        'xtform[filter_category_term]',
        'COM_AUTOTWEET_VIEW_FEED_CATEGORY_TERM',
        'COM_AUTOTWEET_VIEW_FEED_CATEGORY_TERM_DESC'
    );

    echo EHtml::textareaControl(
        $this->item->xtform->get('filter_whitelist', ''),
        'xtform[filter_whitelist]',
        'COM_AUTOTWEET_VIEW_FEED_WHITELIST',
        'COM_AUTOTWEET_VIEW_FEED_WHITELIST_DESC'
    );

    echo EHtml::textareaControl(
        $this->item->xtform->get('filter_blacklist', ''),
        'xtform[filter_blacklist]',
        'COM_AUTOTWEET_VIEW_FEED_BLACKLIST',
        'COM_AUTOTWEET_VIEW_FEED_BLACKLIST_DESC'
    );

    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('save_filter_result', 0),
        'xtform[save_filter_result]',
        'COM_AUTOTWEET_VIEW_FEED_SAVE_FILTER_RESULT',
        'COM_AUTOTWEET_VIEW_FEED_SAVE_FILTER_RESULT_DESC'
    );
    echo '</div>';

    // Import Filter Options - END
