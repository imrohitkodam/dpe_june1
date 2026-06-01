<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

if (!$this->isPrint)
{
	echo $this->loadTemplate('reply');
}
?>
	<h3 class="rst_heading"><?php echo JText::_('RST_CONVERSATION'); ?></h3>
	<div class="row-fluid com-rsticketspro-has-top-margin" id="ticket-buttons">
		<?php
		if (!$this->isPrint)
		{
			if ($this->canViewHistory)
			{
				echo RSTicketsProHelper::renderModal('rsticketsproHistoryModal', array(
					'title'			=> JText::_('RST_TICKET_VIEW_HISTORY'),
					'url'			=> JRoute::_('index.php?option=com_rsticketspro&view=history&id='.$this->ticket->id.'&tmpl=component', false),
					'bodyHeight'	=> 70,
					'backdrop'		=> 'static'));
				?>
				<a href="#" class="btn btn-secondary" onclick="<?php echo RSTicketsProHelper::openModal('rsticketsproHistoryModal'); ?>"><i class="icon-calendar"></i> <?php echo JText::_('RST_TICKET_VIEW_HISTORY'); ?></a>
				<?php
			}
			if ($this->canViewNotes)
			{
				echo RSTicketsProHelper::renderModal('rsticketsproNotesModal', array(
					'title'			=> JText::_('RST_TICKET_VIEW_NOTES'),
					'url'			=> JRoute::_('index.php?option=com_rsticketspro&view=notes&ticket_id='.$this->ticket->id.'&tmpl=component', false),
					'bodyHeight'	=> 70,
					'backdrop'		=> 'static'));
				?>
				<a href="#" class="btn btn-secondary" onclick="<?php echo RSTicketsProHelper::openModal('rsticketsproNotesModal'); ?>"><i class="icon-file"></i> <?php echo $this->ticket->notes ? JText::sprintf('RST_TICKET_VIEW_NOTES_NO', $this->ticket->notes) : JText::_('RST_TICKET_VIEW_NOTES'); ?></a>
				<?php
			}
			?>
			<a href="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=ticket&id='.$this->ticket->id.'&tmpl=component&print=1'); ?>" class="btn btn-secondary" onclick="window.open(this.href,'printWindow','status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=400,height=600,directories=no,location=no'); return false;"><i class="icon-print"></i> <?php echo JText::_('RST_TICKET_PRINT'); ?></a>
			<?php
			if ($this->ticket->status_id == RST_STATUS_CLOSED && $this->canOpenTicket)
			{
				?>
				<a href="<?php echo JRoute::_('index.php?option=com_rsticketspro&task=ticket.reopen&id='.$this->ticket->id); ?>" class="btn btn-success"><i class="icon-ok"></i> <?php echo JText::_('RST_TICKET_OPEN'); ?></a>
				<?php
			}
			elseif ($this->ticket->status_id != RST_STATUS_CLOSED && $this->canCloseTicket)
			{
				?>
				<a href="<?php echo JRoute::_('index.php?option=com_rsticketspro&task=ticket.close&id='.$this->ticket->id); ?>" class="btn btn-warning"><i class="icon-lock"></i> <?php echo JText::_('RST_TICKET_CLOSE'); ?></a>
				<?php
			}

			if ($this->canDeleteTicket)
			{
				JText::script('RST_DELETE_TICKET_CONFIRM');
				?>
				<a href="<?php echo JRoute::_('index.php?option=com_rsticketspro&task=ticket.delete&cid=' . $this->ticket->id . '&return=1&' . JSession::getFormToken() . '=1'); ?>" onclick="return confirm(Joomla.JText._('RST_DELETE_TICKET_CONFIRM'));" class="btn btn-danger pull-right float-end"><i class="icon-trash"></i> <?php echo JText::_('RST_TICKET_DELETE'); ?></a>
				<?php
			}
		}
		else
		{
			?>
			<a href="javascript:void(0)" onclick="window.print();" class="btn btn-primary"><i class="icon-print"></i> <?php echo JText::_('RST_TICKET_PRINT'); ?></a>
			<?php
		}
		?>
	</div>
<?php
foreach ($this->ticketMessages as $message)
{
	$user = $message->user_id != '-1' ? JFactory::getUser($message->user_id) : null;
	$submitter = $message->submitted_by_staff != '0' ? JFactory::getUser($message->submitted_by_staff) : null;
	?>
	<div class="media com-rsticketspro-message<?php echo is_null($user) ? ' alert alert-info' : (RSTicketsProHelper::isStaff($message->user_id) ? ' com-rsticketspro-msg-staff': ' com-rsticketspro-msg-customer'); ?>">
		<?php
		if (!is_null($user))
		{
			?>
			<span class="pull-left">
				<img class="img-polaroid media-object com-rsticketspro-avatar" src="<?php echo $this->getAvatar($message->user_id); ?>" />
			</span>
			<?php
		}
		?>
		<div class="media-body">
			<div class="media-heading">
			<?php
			if (!is_null($user))
			{
				if ($this->showEmailLink)
				{
					$text = JHtml::_('link', 'mailto:' . $this->escape($user->email), $this->escape($user->{$this->userField}));
				}
				else
				{
					$text = $this->escape($user->{$this->userField});
				}

				?>
				<h4 class="com-rsticketspro-submitter"><?php echo $text; ?><?php echo $submitter ? ' ' . JText::sprintf('RST_TICKET_SUBMITTED_BY', $submitter->name) : ''; ?></h4>
				<?php
			}
			?>
				<small><i class="icon-clock"></i> <?php echo $this->showDate($message->date); ?></small>
			</div>
			<div class="com-rsticketspro-msg-container">
				<blockquote class="com-rsticketspro-has-overflow">
					<?php echo RSTicketsProHelper::showMessage($message); ?>
				</blockquote>
				<?php
				if (!empty($message->files))
				{
					?>
					<ul class="com-rsticketspro-msg-files">
						<?php
						foreach ($message->files as $file)
						{
							?>
							<li><?php if (!$this->isPrint && $this->canDeleteMessage($message)) { ?><a class="btn btn-danger btn-small btn-sm" href="<?php echo JRoute::_('index.php?option=com_rsticketspro&task=ticketmessages.deleteattachment&ticket_id=' . $message->ticket_id . '&cid=' . $file->id . '&' . JSession::getFormToken() . '=1'); ?>" onclick="return confirm(Joomla.JText._('RST_DELETE_TICKET_ATTACHMENT_CONFIRM').replace('%s', this.getAttribute('data-filename')));" data-filename="<?php echo $this->escape($file->filename); ?>"><i class="icon-remove"></i></a><?php } ?> <i class="icon-file"></i> <a href="<?php echo JRoute::_('index.php?option=com_rsticketspro&task=ticket.downloadfile&id='.$file->id); ?>"><?php echo JText::sprintf('RST_TICKET_FILE_DOWNLOADS_SMALL', $this->escape($file->filename), $file->downloads); ?></a></li>
							<?php
						}
						?>
					</ul>
					<?php
				}
				if (!$this->isPrint && !is_null($user))
				{
					?>
					<div class="com-rsticketspro-msg-buttons">
						<?php
						if ($this->canEditMessage($message))
						{
							echo RSTicketsProHelper::renderModal('rsticketsproMessageModal' . $message->id, array(
								'title'			=> JText::_('RST_TICKET_EDIT_MESSAGE'),
								'url'			=> JRoute::_('index.php?option=com_rsticketspro&task=ticketmessage.edit&id='.$message->id.'&tmpl=component', false),
								'bodyHeight'	=> 70,
								'backdrop'		=> 'static'));
							?>
							<a class="btn btn-secondary btn-small btn-sm" onclick="<?php echo RSTicketsProHelper::openModal('rsticketsproMessageModal' . $message->id); ?>" href="#"><i class="icon-edit"></i> <?php echo JText::_('RST_TICKET_EDIT_MESSAGE'); ?></a>
							<?php
						}
						if ($this->canDeleteMessage($message))
						{
							?>
							<a class="btn btn-danger btn-small btn-sm" onclick="return confirm(Joomla.JText._('RST_DELETE_TICKET_MESSAGE_CONFIRM'));" href="<?php echo JRoute::_('index.php?option=com_rsticketspro&task=ticketmessages.delete&cid='.$message->id . '&ticket_id=' . $message->ticket_id . '&' . JSession::getFormToken() . '=1'); ?>"><i class="icon-delete"></i> <?php echo JText::_('RST_TICKET_DELETE_MESSAGE'); ?></a>
							<?php
						}
						?>
					</div>
					<?php
				}
				?>
			</div>
		</div>
	</div>
	<?php
}
