<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.keepalive');

if ($this->isStaff)
{
	JHtml::_('script', 'com_rsticketspro/tickets.js', array('relative' => true, 'version' => 'auto'));
}

JText::script('RST_DELETE_TICKETS_CONFIRM');
JText::script('RST_DELETE_TICKET_CONFIRM');

$listOrder 	= $this->escape($this->state->get('list.ordering'));
$listDirn 	= $this->escape($this->state->get('list.direction'));

$script = array();
$script[] = 'Joomla.submitbutton = function(task) {';

if (!empty($this->permissions->delete_ticket))
{
	$script[] = "if (document.getElementById('bulk_delete').value == '1' && !confirm(Joomla.JText._('RST_DELETE_TICKETS_CONFIRM'))) {";
	$script[] = 'return false;';
	$script[] = '}';
}

if (!empty($this->permissions->export_tickets))
{
	$script[] = "RSTicketsPro.exportCSV.totalItems = {$this->totalItems};";
	$script[] = "if (task === 'tickets.exportcsv') {";
	$script[] = "RSTicketsPro.exportCSV.setCSV(0, '');";
	$script[] = "return false;";
	$script[] = "}";

	JHtml::_('script', 'com_rsticketspro/export.js', array('relative' => true, 'version' => 'auto'));
}

$script[] = "Joomla.submitform(task, document.getElementById('adminForm'));";
$script[] = "}";

JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));

if ($this->params->get('show_page_heading', 1))
{
?>
<h1 class="rst-heading"><?php echo $this->escape($this->params->get('page_heading', $this->params->get('page_title'))); ?></h1>
<?php
}

if ($this->globalMessage && $this->globalMessagePosition === 'top')
{
	?>
	<div class="rst-form-section">
		<?php echo $this->globalMessage; ?>
	</div>
	<?php
}

if ($this->isSearching || ($this->isStaff && $this->hasSearches))
{
?>
<div class="well well-small">
	<p>
		<?php
		if ($this->isSearching)
		{
			if ($this->isStaff)
			{
				?>
				<a href="<?php echo JRoute::_('index.php?option=com_rsticketspro&task=predefinedsearch.add'); ?>" class="btn btn-success rst_search"><?php echo JText::_('RST_SAVE_SEARCH'); ?></a>
				<?php
			}
			?>
			<a href="<?php echo JRoute::_('index.php?option=com_rsticketspro&task=resetsearch'); ?>" class="btn btn-danger"><?php echo JText::_('RST_RESET_SEARCH'); ?></a>
			<?php
		}
		if ($this->isStaff && $this->hasSearches)
		{
			?>
			<a href="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=predefinedsearches'); ?>" class="btn btn-secondary rst_manage_searches"><?php echo JText::_('RST_MANAGE_SEARCHES'); ?></a>
			|
			<?php
			foreach ($this->searches as $search)
			{
				if (!$search->current)
				{
					?>
					<a href="<?php echo JRoute::_('index.php?option=com_rsticketspro&task=predefinedsearch.perform&id='.$search->id); ?>" class="btn btn-secondary btn-mini btn-sm <?php echo RSTicketsProHelper::tooltipClass();?>" title="<?php echo RSTicketsProHelper::tooltipText(JText::sprintf('RST_SEARCH_CLICK_DESC', $this->escape($search->name))); ?>"><?php echo $this->escape($search->name); ?></a>
					<?php
				}
				else
				{
					echo $this->escape($search->name);
				}
			}
		}
		?>
	</p>
</div>
<?php } ?>
<div class="clearfix"></div>
<div class="rst-tickets-container">
	<form action="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=tickets'); ?>" method="post" name="adminForm" id="adminForm">
		<?php if ($this->isStaff && !empty($this->permissions->export_tickets)) { ?>
		<div class="com-rsticketspro-progress" id="com-rsticketspro-export-progress" style="display:none">
			<div class="com-rsticketspro-bar" style="width: 0%;">0%</div>
		</div>
		<p>
			<button type="button" class="btn btn-success" onclick="Joomla.submitbutton('tickets.exportcsv');"><?php echo JText::_('COM_RSTICKETSPRO_EXPORT');?></button>
		</p>
		<?php } ?>
		<?php if ($this->isStaff) { ?>
		<div id="bulk_actions" style="display: none;">
			<p><strong><?php echo JText::_('RST_BULK_ACTIONS'); ?></strong></p>
			<?php
			if ($this->permissions->move_ticket)
			{
				echo '<div>' . $this->bulkForm->getField('bulk_department_id')->input . '</div>';
			}
			if ($this->permissions->assign_tickets)
			{
				echo '<div>' . $this->bulkForm->getField('bulk_staff_id')->input . '</div>';
			}
			if ($this->permissions->update_ticket)
			{
				echo '<div>' . $this->bulkForm->getField('bulk_priority_id')->input . '</div>';
			}
			if ($this->permissions->change_ticket_status)
			{
				echo '<div>' . $this->bulkForm->getField('bulk_status_id')->input . '</div>';
			}
			if ($this->autocloseEnabled)
			{
				echo '<div>' . $this->bulkForm->getField('bulk_notify')->input . '</div>';
			}
			if ($this->permissions->delete_ticket)
			{
				echo '<div>' . $this->bulkForm->getField('bulk_delete')->input . '</div>';
			}
			?>

			<p>
				<button type="button" id="rst_update_button" class="btn btn-primary" onclick="Joomla.submitbutton('ticket.bulkupdate');"><?php echo JText::_('RST_UPDATE'); ?></button>
			</p>
		</div>
		<?php } ?>

		<div class="table-responsive">
			<table class="adminlist table table-striped table-condensed table-hover">
			<?php
			if ($this->params->get('show_headings', 1))
			{
				?>
				<thead>
					<tr>
					<?php
					if ($this->params->get('show_offset', 1))
					{
						?>
						<th id="rst_head_item_no" width="1%" class="<?php echo RsticketsproAdapterGrid::show('desktop', 'table-cell'); ?>"><?php echo JText::_('#'); ?></th>
						<?php
					}
					
					if ($this->isStaff)
					{
						?>
						<th id="rst_head_check_all" class="text-center" width="1%">
							<?php echo JHtml::_('grid.checkall'); ?>
						</th>
						<?php
					}
						
					if ($this->params->get('show_date', 1))
					{
						?>
						<th id="rst_head_date" nowrap="nowrap" class="<?php echo RsticketsproAdapterGrid::hide('phone', 'table-cell'); ?>">
							<?php echo JHtml::_('grid.sort', 'RST_TICKET_DATE', 'date', $listDirn, $listOrder, 'none'); ?>
						</th>
						<?php
					}
					
					if ($this->params->get('show_last_reply', 1))
					{
						?>
						<th id="rst_head_last_reply" nowrap="nowrap" class="<?php echo RsticketsproAdapterGrid::show('desktop', 'table-cell'); ?>">
							<?php echo JHtml::_('grid.sort', 'RST_TICKET_LAST_REPLY', 'last_reply', $listDirn, $listOrder, 'none'); ?>
						</th>
						<?php
					}
						
					if ($this->isStaff)
					{
						?>
						<th id="rst_head_flag" nowrap="nowrap" width="1%" class="<?php echo RsticketsproAdapterGrid::show('desktop', 'table-cell'); ?>">
							<?php echo JText::_('RST_FLAGGED'); ?>
						</th>
						<?php
						if ($this->permissions->delete_ticket)
						{
							?>
							<th id="rst_head_delete" nowrap="nowrap" width="5%" class="<?php echo RsticketsproAdapterGrid::hide('phone', 'table-cell'); ?>">
								<?php echo JText::_('RST_DELETE'); ?>
							</th>
						<?php
						}
					}
					?>
						<th id="rst_head_ticket_code" nowrap="nowrap">
						<?php
						if ($this->params->get('show_code', 1))
						{
							echo JHtml::_('grid.sort', 'RST_TICKET_CODE', 'code', $listDirn, $listOrder, 'none') . ' ';
						}

						echo JHtml::_('grid.sort', 'RST_TICKET_SUBJECT', 'subject', $listDirn, $listOrder, 'none');
						?>
						</th>
						
						<?php
						if ($this->params->get('show_customer', 1))
						{
							?>
							<th id="rst_head_customer" nowrap="nowrap">
								<?php echo JHtml::_('grid.sort', 'RST_TICKET_CUSTOMER', 'customer', $listDirn, $listOrder, 'none'); ?>
							</th>
							<?php
						}

						if ($this->params->get('show_priority', 1))
						{
							?>
							<th id="rst_head_priority" nowrap="nowrap" width="1%" class="<?php echo RsticketsproAdapterGrid::hide('phone', 'table-cell'); ?>">
								<?php echo JHtml::_('grid.sort', 'RST_TICKET_PRIORITY', 'priority', $listDirn, $listOrder, 'none'); ?>
							</th>
							<?php
						}
						
						if ($this->params->get('show_status', 1))
						{
							?>
							<th id="rst_head_status" nowrap="nowrap" width="1%" class="<?php echo RsticketsproAdapterGrid::hide('phone', 'table-cell'); ?>">
								<?php echo JHtml::_('grid.sort', 'RST_TICKET_STATUS', 'status', $listDirn, $listOrder, 'none'); ?>
							</th>
							<?php
						}
						
						if ($this->params->get('show_staff', 1))
						{
							?>
							<th id="rst_head_sort" nowrap="nowrap" class="<?php echo RsticketsproAdapterGrid::show('desktop', 'table-cell'); ?>">
								<?php echo JHtml::_('grid.sort', 'RST_TICKET_STAFF', 'staff', $listDirn, $listOrder, 'none'); ?>
							</th>
							<?php
						}

						if ($this->params->get('show_time_spent', 0) && RSTicketsProHelper::getConfig('enable_time_spent'))
						{
							?>
							<th id="rst_head_sort" nowrap="nowrap" class="<?php echo RsticketsproAdapterGrid::show('desktop', 'table-cell'); ?>">
								<?php echo JHtml::_('grid.sort', 'RST_TIME_SPENT', 'time_spent', $listDirn, $listOrder, 'none'); ?>
							</th>
							<?php
						}
						?>
					</tr>
				</thead>
				<?php
			}
			?>
				<tbody>
					<?php
					foreach ($this->items as $i => $item)
					{
						?>
						<tr class="rst_priority_color_<?php echo $item->priority_id; ?> rst-status-<?php echo strtolower(str_replace(array(' ', '_'), '-', preg_replace('/[^a-zA-Z0-9\s-]/', '', $item->status))); ?>">
							<?php if ($this->params->get('show_offset', 1)) { ?>
							<td width="1%" class="rst_cell_item_no text-center <?php echo RsticketsproAdapterGrid::show('desktop', 'table-cell'); ?>"><?php echo $this->pagination->getRowOffset($i); ?></td>
							<?php } ?>

							<?php if ($this->isStaff) { ?>
							<td class="rst_cell_checkbox text-center"><?php echo JHtml::_('grid.id', $i, $item->id); ?></td>
							<?php } ?>

							<?php if ($this->params->get('show_date', 1)) { ?>
							<td class="rst_cell_date <?php echo RsticketsproAdapterGrid::hide('phone', 'table-cell'); ?>"><?php echo $this->escape($this->showDate($item->date)); ?></td>
							<?php } ?>

							<?php if ($this->params->get('show_last_reply', 1)) { ?>
							<td class="rst_cell_last_reply <?php echo RsticketsproAdapterGrid::show('desktop', 'table-cell'); ?>"><?php echo $this->escape($this->showDate($item->last_reply)); ?></td>
							<?php } ?>

							<?php if ($this->isStaff) { ?>
							<td class="rst_cell_flag text-center <?php echo RsticketsproAdapterGrid::show('desktop', 'table-cell'); ?>"><button type="button" class="btn btn-small btn-sm <?php echo $item->flagged ? 'rst_flagged' : 'rst_not_flagged'; ?>" onclick="RSTicketsPro.flagTicket(this, '<?php echo $item->id; ?>');"><i class="rsticketsproicon-star"></i></button></td>

							<?php if ($this->permissions->delete_ticket) { ?>
							<td class="rst_cell_delete_ticket text-center <?php echo RsticketsproAdapterGrid::hide('phone', 'table-cell'); ?>">
								<a class="btn btn-small btn-sm btn-danger rst_button_delete_ticket <?php echo RSTicketsProHelper::tooltipClass();?>" title="<?php echo RSTicketsProHelper::tooltipText(JText::_('RST_TICKET_DELETE_DESC')); ?>" href="<?php echo JRoute::_('index.php?option=com_rsticketspro&task=ticket.delete&cid=' . $item->id . '&return=1&' . JSession::getFormToken() . '=1'); ?>" onclick="return confirm(Joomla.JText._('RST_DELETE_TICKET_CONFIRM'));">&#10006;</a>
							</td>
							<?php } ?>

							<?php } ?>

							<td class="rst_cell_subject">
								<?php if ($item->has_files) { ?>
									<i class="rsticketsproicon-attach"></i>
								<?php } ?>
								<?php if ($this->params->get('show_code', 1)) { ?>
								<a href="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=ticket&id='.$item->id.':'.JFilterOutput::stringURLSafe($item->subject)); ?>"><?php echo $item->code; ?></a>
								<?php if ($this->params->get('show_replies', 1)) { ?>
									(<?php echo $item->replies; ?>)
								<?php } ?>
								<br />
								<?php } ?>
								<a href="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=ticket&id='.$item->id.':'.JFilterOutput::stringURLSafe($item->subject)); ?>"><?php echo $this->escape($item->subject); ?></a>
								<?php if (!$this->params->get('show_code', 1) && $this->params->get('show_replies', 1)) { ?>
								(<?php echo $item->replies; ?>)
								<?php } ?>
								<?php echo $this->notify($item); ?>
							</td>

							<?php if ($this->params->get('show_customer', 1)) { ?>
							<td class="rst_cell_customer"><?php echo $this->escape($item->customer); ?></td>
							<?php } ?>

							<?php if ($this->params->get('show_priority', 1)) { ?>
							<td class="rst_priority_cell <?php echo RsticketsproAdapterGrid::hide('phone', 'table-cell'); ?>"><?php echo JText::_($item->priority); ?></td>
							<?php } ?>

							<?php if ($this->params->get('show_status', 1)) { ?>
							<td class="rst_cell_status <?php echo RsticketsproAdapterGrid::hide('phone', 'table-cell'); ?>"><?php echo JText::_($item->status); ?></td>
							<?php } ?>

							<?php if ($this->params->get('show_staff', 1)) { ?>
							<td class="rst_cell_assigned <?php echo RsticketsproAdapterGrid::show('desktop', 'table-cell'); ?>"><?php echo $item->staff_id ? $this->escape($item->staff) : '<em>'.JText::_('RST_UNASSIGNED').'</em>'; ?></td>
							<?php } ?>

							<?php if ($this->params->get('show_time_spent', 0) && RSTicketsProHelper::getConfig('enable_time_spent')) { ?>
							<td class="<?php echo RsticketsproAdapterGrid::show('desktop', 'table-cell'); ?>"><?php echo $this->showTotal($item->time_spent); ?></td>
							<?php } ?>
						</tr>
					<?php
					}
					?>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="11" class="text-center">
							<div class="pagination"><?php echo $this->pagination->getPagesLinks(); ?></div>
							<?php echo $this->pagination->getPagesCounter(); ?>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>

	<?php
	if ($this->globalMessage && $this->globalMessagePosition === 'bottom')
	{
		?>
		<div class="rst-form-section">
			<?php echo $this->globalMessage; ?>
		</div>
		<?php
	}

	if ($this->showFooter)
	{
		echo $this->footer;
	}
	?>

	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $this->escape($listOrder); ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->escape($listDirn); ?>" />
	<input type="hidden" name="limitstart" value="<?php echo $this->escape($this->limitstart); ?>" />
	<input type="hidden" name="task" value="" />
	<?php
	if ($this->isStaff)
	{
		?>
		<input type="hidden" name="predefined_search" value="<?php echo $this->escape($this->predefinedSearch); ?>" />
		<input type="hidden" name="flagged" value="<?php echo $this->escape($this->flagged); ?>" />
		<?php
	}
	?>
	</form>
</div>