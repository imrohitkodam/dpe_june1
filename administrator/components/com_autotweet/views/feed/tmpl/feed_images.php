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
<h2><?php echo JText::_('COM_AUTOTWEET_VIEW_FEED_TAB_IMGS'); ?></h2>
<?php

    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('imagefromtext', 0),
        'xtform[imagefromtext]',
        'COM_AUTOTWEET_VIEW_FEED_IMAGEFROMTEXT',
        'COM_AUTOTWEET_VIEW_FEED_IMAGEFROMTEXT_DESC',
        'xtformsave_imagefromtext'
    );

    // Param directory="media/feedgator/images/feeds"
    echo EHtml::imageControl(
        $this->item->xtform->get('img'),
        'xtform[img]',
        'COM_AUTOTWEET_VIEW_FEED_DEFAULT_IMG',
        'COM_AUTOTWEET_VIEW_FEED_DEFAULT_IMG',
        null,
        true
    );

    echo EHtml::textControl(
        $this->item->xtform->get('img_class'),
        'xtform[img_class]',
        'COM_AUTOTWEET_VIEW_FEED_IMG_CLASS',
        'COM_AUTOTWEET_VIEW_FEED_IMG_CLASS_DESC'
    );

    echo EHtml::textControl(
        $this->item->xtform->get('img_style'),
        'xtform[img_style]',
        'COM_AUTOTWEET_VIEW_FEED_IMG_STYLE',
        'COM_AUTOTWEET_VIEW_FEED_IMG_STYLE_DESC'
    );

    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('rmv_img_style', 1),
        'xtform[rmv_img_style]',
        'COM_AUTOTWEET_VIEW_FEED_RMV_IMG_STYLE',
        'COM_AUTOTWEET_VIEW_FEED_RMV_IMG_STYLE_DESC'
    );

    // Save Images Options - BEGIN
    if (!PERFECT_PUB_FREE) {
        echo '<div class="save_img">';
        echo EHtmlSelect::yesNoControl(
            $this->item->xtform->get('save_img', 0),
            'xtform[save_img]',
            'COM_AUTOTWEET_VIEW_FEED_SAVE_IMG',
            'COM_AUTOTWEET_VIEW_FEED_SAVE_IMG_DESC',
            'xtformsave_save_img'
        );
        echo '</div>';

        echo '<div class="group-save_img well">';
        echo EHtmlSelect::yesNoControl(
            $this->item->xtform->get('rel_src', 0),
            'xtform[rel_src]',
            'COM_AUTOTWEET_VIEW_FEED_REL_SRC',
            'COM_AUTOTWEET_VIEW_FEED_REL_SRC_DESC'
        );

        $directory = JText::_('COM_AUTOTWEET_NAME');
        echo EHtml::textControl(
            $this->item->xtform->get('img_folder', 'images/'.$directory),
            'xtform[img_folder]',
            'COM_AUTOTWEET_VIEW_FEED_IMG_FOLDER',
            'COM_AUTOTWEET_VIEW_FEED_IMG_FOLDER_DESC'
        );

        $options = [];
        $options[] = ['name' => 'None', 'value' => 0];
        $options[] = ['name' => 'Day', 'value' => 1];
        $options[] = ['name' => 'Week', 'value' => 2];
        $options[] = ['name' => 'Month', 'value' => 3];
        echo EHtmlSelect::btnGroupListControl(
            $this->item->xtform->get('sub_folder', 1),
            'xtform[sub_folder]',
            'COM_AUTOTWEET_VIEW_FEED_SUB_FOLDER',
            'COM_AUTOTWEET_VIEW_FEED_SUB_FOLDER_DESC',
            $options
        );

        $options = [];
        $options[] = ['name' => 'Use Image Title/Alt', 'value' => 0];
        $options[] = ['name' => 'Use Original Filename', 'value' => 1];
        $options[] = ['name' => 'Use md5 hash', 'value' => 2];
        echo EHtmlSelect::btnGroupListControl(
            $this->item->xtform->get('img_name_type', 0),
            'xtform[img_name_type]',
            'COM_AUTOTWEET_VIEW_FEED_IMG_NAME_TYPE',
            'COM_AUTOTWEET_VIEW_FEED_IMG_NAME_TYPE_DESC',
            $options
        );
        /*
                echo EHtmlSelect::yesNoControl(
                        $this->item->xtform->get('alt_img_ext', 0),
                        'xtform[alt_img_ext]',
                        'COM_AUTOTWEET_VIEW_FEED_ALT_IMG_EXT',
                        'COM_AUTOTWEET_VIEW_FEED_ALT_IMG_EXT_DESC');
        */

        echo '</div>';
    }
    // Save Images Options - END

?><?php
