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
<div id="publishing" class="tab-pane fade">

<?php
    echo EHtml::userControl(
    $this->item->xtform->get('default_author'),
    'xtform[default_author]',
    'COM_AUTOTWEET_VIEW_FEED_DEFAULT_AUTHOR',
    'COM_AUTOTWEET_VIEW_FEED_DEFAULT_AUTHOR_DESC',
    null,
    'required'
);
?>

	<div class="control-group">
		<label for="xtformsave_author" class="control-label required" rel="tooltip" data-original-title="<?php
            echo JText::_('COM_AUTOTWEET_VIEW_FEED_SAVE_AUTHOR_DESC');
            ?>"> <?php
            echo JText::_('COM_AUTOTWEET_VIEW_FEED_SAVE_AUTHOR');
            ?>
		</label>
		<div class="controls">
			<?php echo SelectControlHelper::saveauthors($this->item->xtform->get('save_author'), 'xtform[save_author]', ['class' => 'required']); ?>
		</div>
	</div>

	<div class="control-group group-author-alias">
		<label for="author_alias" class="control-label required" rel="tooltip" data-original-title="<?php
        echo JText::_('COM_AUTOTWEET_VIEW_FEED_DEFAULT_AUTHOR_ALIAS_DESC'); ?>"> <?php
        echo JText::_('COM_AUTOTWEET_VIEW_FEED_DEFAULT_AUTHOR_ALIAS'); ?>
		</label>
		<div class="controls">
			<input type="text" name="xtform[author_alias]" id="author_alias" value="<?php echo $this->item->xtform->get('author_alias'); ?>" maxlength="32" />
		</div>
	</div>

	<?php

    echo EHtml::accessLevelControl(
        $this->item->xtform->get('access', 1),
        'xtform[access]',
        'COM_AUTOTWEET_VIEW_FEED_ACCESS_LEVEL',
        'COM_AUTOTWEET_VIEW_FEED_ACCESS_LEVEL_DESC'
    );

    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('front_page', 0),
        'xtform[front_page]',
        'COM_AUTOTWEET_VIEW_FEED_SHOW_FRONT',
        'COM_AUTOTWEET_VIEW_FEED_SHOW_FRONT_DESC'
    );

    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('featured_with_image', 0),
        'xtform[featured_with_image]',
        'COM_AUTOTWEET_VIEW_FEED_SHOW_FEATURED_WITH_IMAGE',
        'COM_AUTOTWEET_VIEW_FEED_SHOW_FEATURED_WITH_IMAGE_DESC'
    );

?>

	<div class="control-group">
		<label for="published" class="control-label" rel="tooltip" data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_FEED_FEED_LANG_DESC'); ?>"><?php
        echo JText::_('COM_AUTOTWEET_VIEW_FEED_FEED_LANG'); ?> </label>
		<div class="controls">
			<?php
            echo SelectControlHelper::languages($this->item->xtform->get('language', '*'), 'xtform[language]'); ?>
		</div>
	</div>

<?php

    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('auto_publish', 1),
        'xtform[auto_publish]',
        'COM_AUTOTWEET_VIEW_FEED_PUB_AUTO',
        'COM_AUTOTWEET_VIEW_FEED_PUB_AUTO_DESC'
    );

    echo EHtmlSelect::booleanListControl(
        $this->item->xtform->get('created_date', 0),
        'xtform[created_date]',
        'COM_AUTOTWEET_VIEW_FEED_CREATED_DATE',
        'COM_AUTOTWEET_VIEW_FEED_CREATED_DATE_DESC',
        'Use Processed Date',
        'Use Feed Date'
    );

    echo EHtmlSelect::booleanListControl(
        $this->item->xtform->get('pub_date', 0),
        'xtform[pub_date]',
        'COM_AUTOTWEET_VIEW_FEED_PUB_DATE',
        'COM_AUTOTWEET_VIEW_FEED_PUB_DATE_DESC',
        'Use Processed Date',
        'Use Feed Date'
    );

    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('advance_date', 1),
        'xtform[advance_date]',
        'COM_AUTOTWEET_VIEW_FEED_ALLOW_FUTURE',
        'COM_AUTOTWEET_VIEW_FEED_ALLOW_FUTURE_DESC'
    );

    $options = [];
    $options[] = ['name' => 'Days', 'value' => 0];
    $options[] = ['name' => 'Hours', 'value' => 1];
    $options[] = ['name' => 'Minutes', 'value' => 2];

    echo EHtml::numericUnitsControl(
        $this->item->xtform->get('publish_duration', '0'),
        'xtform[publish_duration]',
        $this->item->xtform->get('pub_dur_type'),
        'xtform[pub_dur_type]',
        $options,
        'COM_AUTOTWEET_VIEW_FEED_PUB_DUR',
        'COM_AUTOTWEET_VIEW_FEED_PUB_DUR_DESC',
        null,
        'required input-small'
    );

?>

</div>
