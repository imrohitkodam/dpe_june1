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
<div id="workinghours" class="<?php echo AutotweetToolbar::tabPaneActive(); ?>">
<p><?php

    echo JText::_('COM_AUTOTWEET_VIEW_MANAGERS_WORKINGHOURS_DESC');

?></p>
<?php

    echo '<div class="works7x24">';
    echo EHtmlSelect::booleanListControl(
        $this->item->xtform->get('works7x24', 1),
        'xtform[works7x24]',
        'COM_AUTOTWEET_VIEW_MANAGERS_WORKS7X24_LABEL',
        'COM_AUTOTWEET_VIEW_MANAGERS_WORKS7X24_DESC',
        'JYES',
        'JNO',
        'xtformsave_works7x24'
    );
    echo '</div>';

    // Works7x24 Options - BEGIN
    echo '<div class="group-works7x24 well">';

    echo SelectControlHelper::workingDaysControl(
        'xtform[working_days][]',
        $this->item->xtform->get('working_days'),
        'COM_AUTOTWEET_VIEW_MANAGERS_CALENDARDAYS_LABEL',
        'COM_AUTOTWEET_VIEW_MANAGERS_CALENDARDAYS_DESC'
    );

    echo EHtml::timePickerControl(
        $this->item->xtform->get('start_time', '00:00:01'),
        'xtform[start_time]',
        'COM_AUTOTWEET_VIEW_MANAGERS_STARTTIME_LABEL',
        'COM_AUTOTWEET_VIEW_MANAGERS_STARTTIME_DESC',
        null,
        null,
        'manager'
    );

    echo EHtml::timePickerControl(
        $this->item->xtform->get('end_time', '23:59:59'),
        'xtform[end_time]',
        'COM_AUTOTWEET_VIEW_MANAGERS_ENDTIME_LABEL',
        'COM_AUTOTWEET_VIEW_MANAGERS_ENDTIME_DESC',
        null,
        null,
        'manager'
    );

?>

<p>
	<?php echo AutoTweetDefaultView::showWorldClockLink(); ?>
</p>
<?php

echo '</div>';

// Works7x24 Options - END

?>
</div>
