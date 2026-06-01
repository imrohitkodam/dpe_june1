<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

JText::script('COM_RSTICKETSPRO_ARE_YOU_SURE_YOU_WANT_TO_CLEAR_TIME_TRACKING');
JText::script('COM_RSTICKETSPRO_ARE_YOU_SURE_YOU_WANT_TO_DELETE_TIME_TRACKING_RECORD');

if ($this->timeSpentTracking)
{
	if ($this->useTimeCounter)
	{
		?>
		<div class="<?php echo RsticketsproAdapterGrid::row(); ?>" id="rst-timer">
			<div class="<?php echo RsticketsproAdapterGrid::column(12); ?>">
				<?php
				if ($this->ticketTimeState)
				{
					?>
					<div id="timer">
						<div class="clock-wrapper">
							<span class="hours">00</span>
							<span class="dots">:</span>
							<span class="minutes">00</span>
							<span class="dots">:</span>
							<span class="seconds">00</span>
						</div>
					</div>
					<?php
				}
				?>
			</div>
		</div>
		<?php
	}

	if ($this->useTimeCounter || (!empty($this->ticketIntervals) && $this->canDeleteTimeHistory))
	{
		?>
		<p>
			<?php
			if ($this->useTimeCounter)
			{
				?>
				<a href="<?php echo JRoute::_('index.php?option=com_rsticketspro&task=ticket.toggleTime&id='.$this->ticket->id.'&tstate='.($this->ticketTimeState == 1 ? 0 : 1)); ?>"<?php echo ($this->ticketTimeState == 0 ? ' onclick="return confirm(Joomla.JText._(\'COM_RSTICKETSPRO_TIME_BUTTON_CONFIRM_START\'));"' : '');?> class="btn btn-<?php echo ($this->ticketTimeState ? 'danger' : 'success');?>"><?php echo JText::_('COM_RSTICKETSPRO_TIME_BUTTON'.($this->ticketTimeState ? '_STOP' : '_START'));?></a>
				<?php
			}

			if (!empty($this->ticketIntervals) && $this->canDeleteTimeHistory)
			{
				?>
				<a href="<?php echo JRoute::_('index.php?option=com_rsticketspro&task=ticket.clearTimeTracking&id='.$this->ticket->id); ?>" class="btn btn-danger" onclick="return confirm(Joomla.JText._('COM_RSTICKETSPRO_ARE_YOU_SURE_YOU_WANT_TO_CLEAR_TIME_TRACKING'));"><?php echo JText::_('COM_RSTICKETSPRO_TIME_TRACKING_HISTORY_CLEAR');?></a>
				<?php
			}
			?>
		</p>
		<?php
	}

	if (!empty($this->ticketIntervals))
	{
		?>
		<table class="table table-bordered table-condensed table-hover">
			<thead>
			<tr>
				<th><?php echo JText::_('COM_RSTICKETSPRO_TIME_TRACKING_HISTORY_START');?></th>
				<th><?php echo JText::_('COM_RSTICKETSPRO_TIME_TRACKING_HISTORY_END');?></th>
				<th><?php echo JText::_('COM_RSTICKETSPRO_TIME_TRACKING_HISTORY_DURATION');?></th>
				<th><?php echo JText::_('COM_RSTICKETSPRO_TIME_TRACKING_HISTORY_STAFF_MEMBER');?></th>
				<th>&nbsp;</th>
			</tr>
			</thead>
			<tbody>
			<?php
			$total = 0;
			foreach ($this->ticketIntervals as $interval)
			{
				$is_running = $interval->end == '0000-00-00 00:00:00';
				$total += $interval->duration;
				?>
				<tr class="<?php echo ($is_running ? 'error' : 'success');?>">
					<td>
						<?php echo $this->showDate($interval->start);?>
					</td>
					<td>
						<?php echo ($is_running ? JText::_('COM_RSTICKETSPRO_TIME_TRACKING_HISTORY_TRACKING') : $this->showDate($interval->end));?>
					</td>
					<td>
						<?php echo $this->showTotal($interval->duration);?>
					</td>
					<td>
						<?php echo !empty($interval->staff_member) ? $interval->staff_member : ''; ?>
					</td>
					<td class="text-center">
						<?php
						if ($interval->can_delete && !$is_running)
						{
							?>
							<a href="<?php echo JRoute::_('index.php?option=com_rsticketspro&task=ticket.clearTimeTrackingEntry&ticket_id='.$this->ticket->id.'&entry='.$interval->id); ?>" onclick="return confirm(Joomla.JText._('COM_RSTICKETSPRO_ARE_YOU_SURE_YOU_WANT_TO_DELETE_TIME_TRACKING_RECORD'));" class="btn btn-small btn-danger" ><?php echo JText::_('COM_RSTICKETSPRO_TIME_TRACKING_HISTORY_RECORD_DELETE'); ?></a>
							<?php
						}
						?>
					</td>
				</tr>
				<?php
			}
			?>
			</tbody>
			<tfoot>
			<tr>
				<td colspan="2"><?php echo JText::_('COM_RSTICKETSPRO_TIME_TRACKING_TOTAL'); ?></td>
				<td colspan="3"><?php echo $this->showTotal($total); ?></td>
			</tr>
			</tfoot>
		</table>
		<?php
	}
	else
	{
		?>
		<div class="alert alert-warning"><?php echo JText::_('COM_RSTICKETSPRO_TIME_TRACKING_NO_HISTORY_ENTRIES'); ?></div>
		<?php
	}
}

if ($this->timeSpentInput)
{
	$this->form->setFieldAttribute('time_spent', 'description', JText::_('RST_TIME_UNIT_'.RSTicketsProHelper::getConfig('time_spent_unit')));
	echo $this->form->getField('time_spent')->renderField();
	?>
	<button type="button" onclick="Joomla.submitbutton('ticket.savetimespent')" class="btn btn-primary"><?php echo JText::_('RST_UPDATE'); ?></button>
	<?php
}