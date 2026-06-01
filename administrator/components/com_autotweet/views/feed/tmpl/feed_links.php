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
<h3><?php echo JText::_('COM_AUTOTWEET_VIEW_FEED_TAB_TRACKBACKS'); ?></h3>
<?php

    echo '<div class="trackback">';
    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('show_orig_link', 1),
        'xtform[show_orig_link]',
        'COM_AUTOTWEET_VIEW_FEED_SHOW_TRACKBACK',
        'COM_AUTOTWEET_VIEW_FEED_SHOW_TRACKBACK_DESC',
        'xtformsave_trackback'
    );
    echo '</div>';

    // Trackback Options - BEGIN
    echo '<div class="group-trackback well">';
    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('solve_redirection', 0),
        'xtform[solve_redirection]',
        'COM_AUTOTWEET_VIEW_FEED_SHOW_TRACKBACK_SOLVE_REDIRECTION',
        'COM_AUTOTWEET_VIEW_FEED_SHOW_TRACKBACK_SOLVE_REDIRECTION_DESC'
    );

    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('shortlink', 0),
        'xtform[shortlink]',
        'COM_AUTOTWEET_VIEW_FEED_SHOW_TRACKBACK_SHORT',
        'COM_AUTOTWEET_VIEW_FEED_SHOW_TRACKBACK_SHORT_DESC'
    );

    echo EHtml::textControl(
        $this->item->xtform->get('orig_link_text', 'Read more '),
        'xtform[orig_link_text]',
        'COM_AUTOTWEET_VIEW_FEED_TRACKBACK_TEXT',
        'COM_AUTOTWEET_VIEW_FEED_TRACKBACK_TEXT_DESC',
        'orig_link_text',
        100
    );

    $options = [];
    $options[] = ['name' => 'None', 'value' => 'none'];
    $options[] = ['name' => '_blank', 'value' => '_blank'];
    $options[] = ['name' => '_parent', 'value' => '_parent'];
    $options[] = ['name' => '_self', 'value' => '_self'];
    $options[] = ['name' => '_top', 'value' => '_top'];
    $options[] = ['name' => 'Custom', 'value' => 'custom'];
    echo EHtmlSelect::btnGroupListControl(
        $this->item->xtform->get('target_frame', '_blank'),
        'xtform[target_frame]',
        'COM_AUTOTWEET_VIEW_FEED_TARGET_FRAME',
        'COM_AUTOTWEET_VIEW_FEED_TARGET_FRAME_DESC',
        $options
    );

    echo EHtml::textControl(
        $this->item->xtform->get('custom_frame', ''),
        'xtform[custom_frame]',
        'COM_AUTOTWEET_VIEW_FEED_CUST_FRAME',
        'COM_AUTOTWEET_VIEW_FEED_CUST_FRAME_DESC'
    );

    echo EHtml::textControl(
        $this->item->xtform->get('trackback_class', ''),
        'xtform[trackback_class]',
        'COM_AUTOTWEET_VIEW_FEED_TRACKBACK_CLASS',
        'COM_AUTOTWEET_VIEW_FEED_TRACKBACK_CLASS_DESC'
    );

    echo EHtml::textControl(
        $this->item->xtform->get('trackback_rel', ''),
        'xtform[trackback_rel]',
        'COM_AUTOTWEET_VIEW_FEED_TRACKBACK_REL',
        'COM_AUTOTWEET_VIEW_FEED_TRACKBACK_REL_DESC'
    );
    echo '</div>';

    // Trackback Options - END
