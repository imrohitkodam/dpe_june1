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
<div id="tagging" class="tab-pane fade">

<?php

    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('compute_tags', 1),
        'xtform[compute_tags]',
        'COM_AUTOTWEET_VIEW_FEED_ADD_META',
        'COM_AUTOTWEET_VIEW_FEED_ADD_META_DESC'
    );

    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('use_addkeywords', 1),
        'xtform[use_addkeywords]',
        'COM_AUTOTWEET_VIEW_FEED_USE_ADDKEYWORDS',
        'COM_AUTOTWEET_VIEW_FEED_USE_ADDKEYWORDS_DESC'
    );

    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('use_yahoo_te', 0),
        'xtform[use_yahoo_te]',
        'COM_AUTOTWEET_VIEW_FEED_USE_YAHOO',
        'COM_AUTOTWEET_VIEW_FEED_USE_YAHOO_DESC'
    );

    echo EHtml::textControl(
        $this->item->xtform->get('yahoo_app_id', ''),
        'xtform[yahoo_app_id]',
        'COM_AUTOTWEET_VIEW_FEED_YAHOO_ID',
        'COM_AUTOTWEET_VIEW_FEED_YAHOO_ID_DESC'
    );

    echo EHtml::textControl(
        $this->item->xtform->get('max_tags'),
        'xtform[max_tags]',
        'COM_AUTOTWEET_VIEW_FEED_MAX_TAGS',
        'COM_AUTOTWEET_VIEW_FEED_MAX_TAGS_DESC'
    );

    echo EHtml::textControl(
        $this->item->xtform->get('min_tag_chars'),
        'xtform[min_tag_chars]',
        'COM_AUTOTWEET_VIEW_FEED_MIN_TAGS',
        'COM_AUTOTWEET_VIEW_FEED_MIN_TAGS_DESC'
    );

    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('use_ignore_list', 0),
        'xtform[use_ignore_list]',
        'COM_AUTOTWEET_VIEW_FEED_IGNORE_LIST',
        'COM_AUTOTWEET_VIEW_FEED_IGNORE_LIST_DESC'
    );

    echo EHtml::textareaControl(
        $this->item->xtform->get('ignore_list'),
        'xtform[ignore_list]',
        'COM_AUTOTWEET_VIEW_FEED_ADDITIONAL_IGNORE_LIST',
        'COM_AUTOTWEET_VIEW_FEED_ADDITIONAL_IGNORE_LIST_DESC'
    );

    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('save_feed_cats', 0),
        'xtform[save_feed_cats]',
        'COM_AUTOTWEET_VIEW_FEED_FEED_CATS',
        'COM_AUTOTWEET_VIEW_FEED_FEED_CATS_DESC'
    );

    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('save_sect_cats', 0),
        'xtform[save_sect_cats]',
        'COM_AUTOTWEET_VIEW_FEED_JOOMLA_SEC_CAT',
        'COM_AUTOTWEET_VIEW_FEED_JOOMLA_SEC_CAT_DESC'
    );

?>

</div>
