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
<h2><?php echo JText::_('COM_AUTOTWEET_VIEW_FEED_TAB_TEXT_EDITION'); ?></h2>

	<div class="control-group">
		<label for="xtformid4610" class="control-label required" rel="tooltip" data-original-title="<?php
            echo JText::_('COM_AUTOTWEET_VIEW_FEED_ADD_AUTHOR_DESC');
            ?>"> <?php
            echo JText::_('COM_AUTOTWEET_VIEW_FEED_ADD_AUTHOR');
            ?>
		</label>
		<div class="controls">
			<?php echo SelectControlHelper::authorarticles($this->item->xtform->get('author_article'), 'xtform[author_article]', ['class' => 'required']); ?>
		</div>
	</div>

<?php

    /*
    echo EHtmlSelect::yesNoControl(
            $this->item->xtform->get('create_art', 1),
            'xtform[create_art]',
            'COM_AUTOTWEET_VIEW_FEED_CREATE_ART',
            'COM_AUTOTWEET_VIEW_FEED_CREATE_ART_DESC');
    */

    echo '<div class="combine">';
    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('onlyintro', 1),
        'xtform[onlyintro]',
        'COM_AUTOTWEET_VIEW_FEED_ONLY_INTRO',
        'COM_AUTOTWEET_VIEW_FEED_ONLY_INTRO_DESC',
        'xtformsave_combine'
    );
    echo '</div>';

    // Combine Options - BEGIN

    /*
     echo '<div class="group-combine well">';
    echo EHtmlSelect::yesNoControl(
            $this->item->xtform->get('combine_text', 0),
            'xtform[combine_text]',
            'COM_AUTOTWEET_VIEW_FEED_COMBINE_TEXT',
            'COM_AUTOTWEET_VIEW_FEED_COMBINE_TEXT_DESC');
    echo '</div>';
    */
    // Combine Options - END

    $optionsLength = [];
    $optionsLength[] = ['name' => 'Chars', 'value' => 'char'];
    $optionsLength[] = ['name' => 'Words', 'value' => 'word'];
    $optionsLength[] = ['name' => 'Sents', 'value' => 'sent'];

    echo EHtml::numericUnitsControl(
        $this->item->xtform->get('trim_to', 0),
        'xtform[trim_to]',
        $this->item->xtform->get('trim_type', 'sent'),
        'xtform[trim_type]',
        $optionsLength,
        'COM_AUTOTWEET_VIEW_FEED_TRIM_TO',
        'COM_AUTOTWEET_VIEW_FEED_TRIM_TO_DESC',
        null,
        'required input-small'
    );

    $options = [];
    $options[] = ['name' => 'Content', 'value' => 1];
    $options[] = ['name' => 'Description', 'value' => 0];
    $options[] = ['name' => 'Description + Content', 'value' => 2];

    echo EHtmlSelect::btnGroupListControl(
        $this->item->xtform->get('show_html', 1),
        'xtform[show_html]',
        'COM_AUTOTWEET_VIEW_FEED_SHOW_HTML',
        'COM_AUTOTWEET_VIEW_FEED_SHOW_HTML_DESC',
        $options
    );

    echo EHtml::numericUnitsControl(
        $this->item->xtform->get('max_length', '0'),
        'xtform[max_length]',
        $this->item->xtform->get('max_length_type', 'char'),
        'xtform[max_length_type]',
        $optionsLength,
        'COM_AUTOTWEET_VIEW_FEED_MAX_LENGTH',
        'COM_AUTOTWEET_VIEW_FEED_MAX_LENGTH_DESC',
        null,
        'required input-small'
    );

    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('dotdotdot', 0),
        'xtform[dotdotdot]',
        'COM_AUTOTWEET_VIEW_FEED_DOTDOTDOT',
        'COM_AUTOTWEET_VIEW_FEED_DOTDOTDOT_DESC'
    );

    echo EHtml::textareaControl(
        $this->item->xtform->get('introtext'),
        'xtform[introtext]',
        'COM_AUTOTWEET_VIEW_FEED_DEFAULT_INTRO',
        'COM_AUTOTWEET_VIEW_FEED_DEFAULT_INTRO_DESC'
    );

    echo '<div class="source-fulltext">';
    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('fulltext', 0),
        'xtform[fulltext]',
        'COM_AUTOTWEET_VIEW_FEED_FULL_TEXT',
        'COM_AUTOTWEET_VIEW_FEED_FULL_TEXT_DESC',
        'xtformsave_sourcefulltext'
    );
    echo '</div>';

    // Source Full Text Options - BEGIN
    echo '<div class="group-source-fulltext well">';

    $options = [];
    $options[] = ['name' => 'Default', 'value' => 0];
    $options[] = ['name' => 'Minor', 'value' => 1];
    $options[] = ['name' => 'Moderate', 'value' => 2];
    $options[] = ['name' => 'Full', 'value' => 3];
    echo EHtmlSelect::btnGroupListControl(
        $this->item->xtform->get('relax_parsing', 0),
        'xtform[relax_parsing]',
        'COM_AUTOTWEET_VIEW_FEED_RELAX_PARSING',
        'COM_AUTOTWEET_VIEW_FEED_RELAX_PARSING_DESC',
        $options
    );

    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('readability_title', 1),
        'xtform[readability_title]',
        'COM_AUTOTWEET_VIEW_FEED_READABILITY_TITLE',
        'COM_AUTOTWEET_VIEW_FEED_READABILITY_TITLE_DESC'
    );
?>
	<h4><?php echo JText::_('COM_AUTOTWEET_VIEW_FEED_TAB_LINKS'); ?></h4>
<?php

    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('link_table', 1),
        'xtform[link_table]',
        'COM_AUTOTWEET_VIEW_FEED_LINK_TABLE',
        'COM_AUTOTWEET_VIEW_FEED_LINK_TABLE_DESC'
    );

    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('link_nofollow', 1),
        'xtform[link_nofollow]',
        'COM_AUTOTWEET_VIEW_FEED_LINK_NOFOLLOW',
        'COM_AUTOTWEET_VIEW_FEED_LINK_NOFOLLOW_DESC'
    );

    $options = [];
    $options[] = ['name' => 'None', 'value' => 'none'];
    $options[] = ['name' => '_blank', 'value' => '_blank'];
    $options[] = ['name' => '_parent', 'value' => '_parent'];
    $options[] = ['name' => '_self', 'value' => '_self'];
    $options[] = ['name' => '_top', 'value' => '_top'];
    echo EHtmlSelect::btnGroupListControl(
        $this->item->xtform->get('link_target', '_blank'),
        'xtform[link_target]',
        'COM_AUTOTWEET_VIEW_FEED_LINK_TARGET',
        'COM_AUTOTWEET_VIEW_FEED_LINK_TARGET_DESC',
        $options
    );

    echo '</div>';

    // Source Full Text Options - END
