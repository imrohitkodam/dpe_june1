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

$listOrder 	= $this->escape($this->state->get('list.ordering'));
$listDirn 	= $this->escape($this->state->get('list.direction'));

JText::script('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST');
JText::script('RST_DELETE_TICKET_NOTE_CONFIRM');
?>

<form action="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=notes&ticket_id='.$this->id.'&tmpl=component'); ?>" method="post" name="adminForm" id="adminForm">
	<div class="rst_button_spacer">
	<?php
	if ($this->permissions->add_note)
	{
		?>
		<button type="button" class="btn btn-success" onclick="Joomla.submitbutton('note.add');"><i class="icon-plus"></i> <?php echo JText::_('RST_TICKET_ADD_NOTE'); ?></button>
		<?php
	}
	if ($this->permissions->delete_note || $this->permissions->delete_note_staff)
	{
		?>
		<button type="button" class="btn btn-danger" onclick="if (document.adminForm.boxchecked.value === '0') { alert(Joomla.JText._('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST')); } else { if (confirm(Joomla.JText._('RST_DELETE_TICKET_NOTE_CONFIRM'))) { Joomla.submitbutton('notes.delete'); } }"><i class="icon-delete"></i> <?php echo JText::_('RST_TICKET_DELETE_NOTE'); ?></button>
		<?php
	}
	?>
	</div>
	<?php
	if (empty($this->items))
	{
		?>
		<div class="alert alert-info">
			<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?php echo JText::_('INFO'); ?></span>
			<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
		<?php
	}
	else
	{
		?>
		<table class="table table-striped table-condensed">
			<thead>
			<tr>
				<th width="1%" nowrap="nowrap"><?php echo JHtml::_('grid.checkall'); ?></th>
				<th><?php echo JHtml::_('grid.sort', 'RST_HISTORY_DATE', 'date', $listDirn, $listOrder); ?></th>
				<th><?php echo JText::_('RST_NOTES_USER'); ?></th>
				<th><?php echo JText::_('RST_TICKET_NOTE'); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ($this->items as $i => $item)
			{
				$canDelete = ($this->permissions->delete_note && $item->user_id == $this->userId) || ($this->permissions->delete_note_staff && $item->user_id != $this->userId);
				$canEdit   = ($this->permissions->update_note && $item->user_id == $this->userId) || ($this->permissions->update_note_staff && $item->user_id != $this->userId);
				?>
				<tr>
					<td width="1%" nowrap="nowrap"><?php echo JHtml::_('grid.id', $i, $item->id, !$canDelete); ?></td>
					<td width="1%" nowrap="nowrap"><?php echo $this->showDate($item->date); ?></td>
					<td width="1%" nowrap="nowrap"><?php echo $this->escape($this->showUser($item->user_id)); ?></td>
					<td>
						<p><?php echo nl2br($this->escape($item->text)); ?></p>
						<?php
						if ($canEdit)
						{
							?>
							<p><a class="btn btn-secondary btn-mini btn-sm" href="<?php echo JRoute::_('index.php?option=com_rsticketspro&task=note.edit&tmpl=component&ticket_id='.$this->id.'&id='.(int) $item->id); ?>"><?php echo JText::_('RST_TICKET_EDIT_NOTE'); ?></a></p>
							<?php
						}
						?>
					</td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>
		<?php
		echo $this->pagination->getListFooter();
	}

	echo JHtml::_( 'form.token' );
	?>
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
</form>