<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

use XTP_BUILD\Extly\Infrastructure\Service\Cms\Joomla\ScriptHelper;

?>
<div id="agendas" ng-controller="AgendaController as agendaCtrl">
<?php

$agendas = '';
ScriptHelper::addScriptDeclaration("var agendasToLoad = '{$agendas}';");

$now = \Joomla\CMS\Factory::getDate()->toUnix();
$now = $now - ($now % 60) + 5 * 60;
$now = \Joomla\CMS\Factory::getDate($now)->format(JText::_('COM_AUTOTWEET_DATE_FORMAT'));
[$date, $time] = EParameter::getDateTimeParts($now);

$control1 = EHtml::datePickerField(
    $now,
    'scheduling_date',
    'scheduling_date',
    [
        'ng-model' => 'agendaCtrl.scheduling_date_value',

        // 'field-class' => 'xt-col-span-8',
        'class' => 'xt-col-span-6',
    ]
);

$control2 = EHtml::timePickerField(
    $now,
    'scheduling_time',
    'scheduling_time',
    [
        'ng-model' => 'agendaCtrl.scheduling_time_value',

        // 'field-class' => 'xt-col-span-8',
        'class' => 'xt-col-span-6',
    ]
);

echo EHtml::genericControl(
    'COM_AUTOTWEET_VIEW_ITEMEDITOR_SCHEDULER',
    'COM_AUTOTWEET_VIEW_ITEMEDITOR_SCHEDULER_DESC',
    'scheduling_date_d',
    $control1
);

echo EHtml::genericControl(
    '',
    '',
    'scheduling_date_m',
    $control2
);

?>

<div class="control-group ">
		<div class="controls xt-grid">
			<div class="xt-col-span-6 xt-text-right">
				<a class="btn btn-info addbutton" ng-click="agendaCtrl.add()"><i class="xticon fas fa-plus"></i> <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_ADD_AGENDA'); ?></a>
			</div>
			<div class="xt-col-span-6 xt-text-left">
			    <?php echo AutoTweetDefaultView::showWorldClockLink(); ?>
		    </div>
		</div>
	</div>

	<legend>
	<?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_AGENDA'); ?>
	</legend>

	<table class="table table-bordered">
		<thead>
			<tr>
				<th class="title"><?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_DATE'); ?></th>
				<th><?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_TIME'); ?></th>
				<th></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="3"></td>
			</tr>
		</tfoot>
		<tbody id="agendalist">
			<tr ng-repeat="agenda in agendaCtrl.agendas">
				<td>{{ agenda.agendaDate }}</td>
				<td>{{ agenda.agendaTime }}</td>
				<td><a class="destroy" ng-click="agendaCtrl.remove(agenda)"><i class="xticon far fa-trash-alt"></i></a></td>
				<input type="hidden" value="{{ agenda.agendaDate }} {{ agenda.agendaTime }}:00" name="agenda[]">
			</tr>
		</tbody>
	</table>

</div>
