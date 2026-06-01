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
JHtml::_('formbehavior.chosen', '.advancedSelect');

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
?>
<form action="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=tickets'); ?>" method="post" name="adminForm" id="adminForm">
	<?php
	echo RsticketsproAdapterGrid::sidebar();

	echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));

	if (!empty($this->permissions->export_tickets))
	{
		?>
		<div class="com-rsticketspro-progress" id="com-rsticketspro-export-progress" style="display:none">
			<div class="com-rsticketspro-bar" style="width: 0%;">0%</div>
		</div>
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
				<a href="<?php echo JRoute::_('index.php?option=com_rsticketspro&task=search.reset'); ?>" class="btn btn-danger"><?php echo JText::_('RST_RESET_SEARCH'); ?></a>
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
		<?php
	}
	?>
	
	<?php
	if ($this->isStaff)
	{
		echo JHtml::_('bootstrap.renderModal', 'rsticketsproBulkModal', array(
			'title' => JText::_('RST_BULK_ACTIONS'),
			'footer' => $this->loadTemplate('bulk_footer'),
			'height' => 400,
			'backdrop' => 'static'), $this->loadTemplate('bulk_body'));
	}
	?>
    <div class="table-responsive">
		<table class="adminlist table table-striped" id="articleList">
			<thead>
			<tr>
				<th class="<?php echo RsticketsproAdapterGrid::show('desktop', 'table-cell'); ?>" width="1%" nowrap="nowrap"><?php echo JText::_( '#' ); ?></th>
				<th width="1%" nowrap="nowrap"><?php echo JHtml::_('grid.checkall'); ?></th>
				<th class="<?php echo RsticketsproAdapterGrid::hide('phone', 'table-cell'); ?>" width="140"><?php echo JHtml::_('searchtools.sort', 'RST_TICKET_DATE', 'date', $listDirn, $listOrder); ?></th>
				<th class="<?php echo RsticketsproAdapterGrid::show('desktop', 'table-cell'); ?>" width="140"><?php echo JHtml::_('searchtools.sort', 'RST_TICKET_LAST_REPLY', 'last_reply', $listDirn, $listOrder); ?></th>
				<th class="<?php echo RsticketsproAdapterGrid::show('desktop', 'table-cell'); ?>" width="1%" nowrap="nowrap"><?php echo JHtml::_('searchtools.sort', 'RST_FLAGGED', 'flagged', $listDirn, $listOrder); ?></th>
				<?php if ($this->permissions->delete_ticket) { ?>
					<th width="1%" nowrap="nowrap"><?php echo JText::_('RST_DELETE'); ?></th>
				<?php } ?>
				<th><?php echo JHtml::_('searchtools.sort', 'RST_TICKET_CODE', 'code', $listDirn, $listOrder); ?> <?php echo JHtml::_('searchtools.sort', 'RST_TICKET_SUBJECT', 'subject', $listDirn, $listOrder); ?></th>
				<th><?php echo JHtml::_('searchtools.sort', 'RST_TICKET_CUSTOMER', 'customer', $listDirn, $listOrder); ?></th>
				<th class="<?php echo RsticketsproAdapterGrid::hide('phone', 'table-cell'); ?>" width="1%" nowrap="nowrap"><?php echo JHtml::_('searchtools.sort', 'RST_TICKET_PRIORITY', 'priority', $listDirn, $listOrder); ?></th>
				<th class="<?php echo RsticketsproAdapterGrid::hide('phone', 'table-cell'); ?>" width="1%" nowrap="nowrap"><?php echo JHtml::_('searchtools.sort', 'RST_TICKET_STATUS', 'status', $listDirn, $listOrder); ?></th>
				<th class="<?php echo RsticketsproAdapterGrid::show('desktop', 'table-cell'); ?>"><?php echo JHtml::_('searchtools.sort', 'RST_TICKET_STAFF', 'staff', $listDirn, $listOrder); ?></th>
				<?php if (RSTicketsProHelper::getConfig('enable_time_spent')) { ?>
				<th class="<?php echo RsticketsproAdapterGrid::show('desktop', 'table-cell'); ?>"><?php echo JHtml::_('searchtools.sort', 'RST_TIME_SPENT', 'time_spent', $listDirn, $listOrder); ?></th>
				<?php } ?>
			</tr>
			</thead>
	<?php
	foreach ($this->items as $i => $item)
	{
		$grid = JHtml::_('grid.id', $i, $item->id);
		$link = JRoute::_('index.php?option=com_rsticketspro&view=ticket&id='.$item->id);
		?>
		<tr class="rst_priority_color_<?php echo $item->priority_id; ?>">
			<td class="<?php echo RsticketsproAdapterGrid::show('desktop', 'table-cell'); ?>" width="1%" nowrap="nowrap"><?php echo $this->pagination->getRowOffset($i); ?></td>
			<td width="1%" nowrap="nowrap"><?php echo $grid; ?></td>
			<td class="<?php echo RsticketsproAdapterGrid::hide('phone', 'table-cell'); ?>"><?php echo $this->escape($this->showDate($item->date)); ?></td>
			<td class="<?php echo RsticketsproAdapterGrid::show('desktop', 'table-cell'); ?>"><?php echo $this->escape($this->showDate($item->last_reply)); ?></td>
			<td class="<?php echo RsticketsproAdapterGrid::show('desktop', 'table-cell'); ?> text-center" align="center"><button type="button" class="btn btn-small btn-sm <?php echo $item->flagged ? 'rst_flagged' : 'rst_not_flagged'; ?>" onclick="RSTicketsPro.flagTicket(this, '<?php echo $item->id; ?>');"><i class="rsticketsproicon-star"></i></button></td>
			<?php if ($this->permissions->delete_ticket) { ?>
				<td class="text-center" align="center">
					<a class="btn btn-small btn-sm btn-danger rst_button_delete_ticket <?php echo RSTicketsProHelper::tooltipClass();?>" title="<?php echo RSTicketsProHelper::tooltipText(JText::_('RST_TICKET_DELETE_DESC')); ?>" href="<?php echo JRoute::_('index.php?option=com_rsticketspro&task=ticket.delete&cid=' . $item->id . '&return=1&' . JSession::getFormToken() . '=1'); ?>" onclick="return confirm(Joomla.JText._('RST_DELETE_TICKET_CONFIRM'));">&#10006;</a>
				</td>
			<?php } ?>
			<td>
			<?php if ($item->has_files) { ?>
				<i class="rsticketsproicon-attach"></i>
			<?php } ?>
			<a href="<?php echo $link; ?>"><?php echo $item->code; ?></a> (<?php echo $item->replies; ?>)
			<br />
			<a href="<?php echo $link; ?>"><?php echo $this->escape($item->subject); ?></a>
			<?php echo $this->notify($item); ?>
			</td>
			<td><a href="<?php echo JRoute::_('index.php?option=com_users&view=user&task=user.edit&id='.$item->customer_id); ?>"><?php echo $this->escape($item->customer); ?></a></td>
			<td class="rst_priority_cell <?php echo RsticketsproAdapterGrid::hide('phone', 'table-cell'); ?>"><?php echo JText::_($item->priority); ?></td>
			<td class="<?php echo RsticketsproAdapterGrid::hide('phone', 'table-cell'); ?>"><?php echo JText::_($item->status); ?></td>
			<td class="<?php echo RsticketsproAdapterGrid::show('desktop', 'table-cell'); ?>"><?php echo $item->staff_id ? $this->escape($item->staff) : '<em>'.JText::_('RST_UNASSIGNED').'</em>'; ?></td>
			<?php if (RSTicketsProHelper::getConfig('enable_time_spent')) { ?>
			<td class="<?php echo RsticketsproAdapterGrid::show('desktop', 'table-cell'); ?>"><?php echo $this->showTotal($item->time_spent); ?></td>
			<?php } ?>
		</tr>
		<?php
	}
	?>
		</table>
	    <?php echo $this->pagination->getListFooter(); ?>
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
	?>
	</div>
	
	<?php echo JHtml::_( 'form.token' ); ?>
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="option" value="com_rsticketspro" />
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