<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

$use_magnific = RSTicketsProHelper::getConfig('use_magnific_popup');
?>
<div class="row">
	<?php
	if ($this->ticket->school['agency_id'] == null)
	{
		?>
		<div class="col-sm-12">
			<p class="alert alert-warning"><?php echo Text::sprintf('RST_TICKET_COMPLETE_TICKET_INFO_BEFORE_REPLYING', Text::_('COM_MULTIAGENCY_ORGANISATION')); ?></p>
		</div>
	<?php
	}
	?>
	<div class="col-sm-2"><h3 class="rst_heading"><?php echo Text::_('RST_CONVERSATION'); ?></h3></div>
	<div class="col-sm-10">
		<div class="pull-right">
				<?php
				if (!$this->isPrint)
				{
					echo $this->loadTemplate('reply');
				}
				?>
		</div>
	</div>
</div>

<div class="row-fluid com-rsticketspro-has-top-margin" id="ticket-buttons">
	<?php
	if (!$this->isPrint)
	{
		if ($this->canViewHistory)
		{
			if ($use_magnific)
			{
				echo RSTicketsProHelper::renderMagnificPopup(
					'rsticketsproHistoryModal',
					array(
						'title' => Text::_('RST_TICKET_VIEW_HISTORY'),
						'url'  => Route::_('index.php?option=com_rsticketspro&view=history&id=' . $this->ticket->id . '&tmpl=component', false),
						'height'   => 400
					)
				);
			}
			else
			{
				echo HTMLHelper::_('bootstrap.renderModal', 'rsticketsproHistoryModal', array(
					'title' => Text::_('RST_TICKET_VIEW_HISTORY'),
					'url' => Route::_('index.php?option=com_rsticketspro&view=history&id=' . $this->ticket->id . '&tmpl=component', false),
					'height' => 400,
					'backdrop' => 'static')
					);
			}
			?>
			<a
				href="#" class="btn mb-5"
				onclick="<?php echo ($use_magnific ? 'RSTicketsPro.openMagnificModal(event, \'#rsticketsproHistoryModal\');' : 'jQuery(\'#rsticketsproHistoryModal\').modal(\'show\');'); ?>">
				<i class="icon-calendar"></i> <?php echo Text::_('RST_TICKET_VIEW_HISTORY'); ?></a>
			<?php
		}

		if ($this->canViewNotes)
		{
			if ($use_magnific)
			{
				echo RSTicketsProHelper::renderMagnificPopup('rsticketsproNotesModal', array(
					'title'  => Text::_('RST_TICKET_VIEW_NOTES'),
					'url'    => Route::_('index.php?option=com_rsticketspro&view=notes&ticket_id=' . $this->ticket->id . '&tmpl=component', false),
					'height' => 400)
				);
			}
			else
			{
				echo HTMLHelper::_('bootstrap.renderModal', 'rsticketsproNotesModal', array(
					'title'    => Text::_('RST_TICKET_VIEW_NOTES'),
					'url'      => Route::_('index.php?option=com_rsticketspro&view=notes&ticket_id=' . $this->ticket->id . '&tmpl=component', false),
					'height'   => 400,
					'backdrop' => 'static')
					);
			}
			?>
			<a href="#" class="btn mb-5 <?php echo $this->ticket->notes ? 'ticketnotebtn font-700' : '';?>"
			onclick="<?php echo ($use_magnific ? 'RSTicketsPro.openMagnificModal(event, \'#rsticketsproNotesModal\');' : 'jQuery(\'#rsticketsproNotesModal\').modal(\'show\');'); ?>">
				<i class="icon-file"></i>
				<?php echo $this->ticket->notes ? Text::sprintf('RST_TICKET_VIEW_NOTES_NO', $this->ticket->notes) : Text::_('RST_TICKET_VIEW_NOTES'); ?>
			</a>
		<?php
		}
	}

	if (!$this->isPrint)
	{
		if ($this->ticket->status_id == RST_STATUS_CLOSED)
		{
			if ($this->canOpenTicket)
			{
				?>
				<a href="<?php echo Route::_('index.php?option=com_rsticketspro&task=ticket.reopen&id=' . $this->ticket->id); ?>" class="btn mb-5">
					 <i class="fa fa-folder-open"></i> <?php echo Text::_('RST_TICKET_OPEN'); ?>
				</a>
			<?php
			}
		}
		else
		{
			if ($this->canCloseTicket)
			{
				?>
				<a href="<?php echo Route::_('index.php?option=com_rsticketspro&task=ticket.close&id=' . $this->ticket->id); ?>" class="btn mb-5">
					<i class="icon-lock"></i> <?php echo Text::_('RST_TICKET_CLOSE'); ?>
				</a>
			<?php
			}
		}
	} ?>
</div>

<?php
foreach ($this->ticketMessages as $message)
{
	$user              = $message->user_id != '-1' ? Factory::getUser($message->user_id) : null;
	$statusChangeClass = is_null($user) ? ' alert alert-info' : (RSTicketsProHelper::isStaff($message->user_id) ? ' com-rsticketspro-msg-staff': ' com-rsticketspro-msg-customer');

	if (!is_null($user))
	{
		$avatars = RSTicketsProHelper::getConfig('avatars');
		$appendClass = (empty(RSTicketsProHelper::getConfig('avatars'))) ? 'com-rsticketspro-defaultUser' : '';
		?>
		<div class="media com-rsticketspro-message<?php echo $statusChangeClass ?>">
			<!-- User Avtar of default user image-->
			<span class="pull-left">
				<img class="img-polaroid media-object com-rsticketspro-avatar <?php echo $appendClass ?>" src="<?php echo $this->getAvatar($message->user_id); ?>" />
			</span>

			<div class="media-body">
				<h4 class="media-heading">
					<?php
					if ($this->showEmailLink)
					{
					?>
						<a href="mailto:<?php echo $this->escape($user->email); ?>">
					<?php
					}

					echo $this->escape($user->{$this->userField});

					if ($this->showEmailLink)
					{
						?>
						</a>
						<?php
					}
					?>
				</h4>

				<p><small><i class="icon-clock"></i> <?php echo $this->showDate($message->date);?></small></p>
				<blockquote class="com-rsticketspro-has-overflow">
					<?php echo RSTicketsProHelper::showMessage($message); ?>
				</blockquote>

				<?php
				if (!empty($message->files))
				{
					?>
					<ul>
						<?php
						foreach ($message->files as $file)
						{
							?>
							<li>
								<i class="icon-file"></i>
								<a href="<?php echo Route::_('index.php?option=com_rsticketspro&task=ticket.downloadfile&id=' . $file->id); ?>">
									<?php echo Text::sprintf('RST_TICKET_FILE_DOWNLOADS_SMALL', $this->escape($file->filename), $file->downloads);?>
								</a>
							</li>
						<?php
						}
						?>
					</ul>
				<?php
				}

				if (!$this->isPrint)
				{
					?>
					<div>
						<?php
						if ($this->canEditMessage($message))
						{
							if ($use_magnific)
							{
								echo RSTicketsProHelper::renderMagnificPopup('rsticketsproMessageModal' . $message->id, array(
									'title'    => Text::_('RST_TICKET_EDIT_MESSAGE'),
									'url' 	   => Route::_('index.php?option=com_rsticketspro&task=ticketmessage.edit&id=' . $message->id . '&tmpl=component', false),
									'height'   => 474
								)
								);
							}
							else
							{
								echo HTMLHelper::_('bootstrap.renderModal', 'rsticketsproMessageModal' . $message->id, array(
									'title'    => Text::_('RST_TICKET_EDIT_MESSAGE'),
									'url' 	   => Route::_('index.php?option=com_rsticketspro&task=ticketmessage.edit&id=' . $message->id . '&tmpl=component', false),
									'height'   => 475,
									'backdrop' => 'static')
									);
							}
							?>
							<a class="btn"
							onclick="<?php echo ($use_magnific ? 'RSTicketsPro.openMagnificModal(event, \'#rsticketsproMessageModal' . $message->id . '\');' : 'jQuery(\'#rsticketsproMessageModal' . $message->id . '\').modal(\'show\');'); ?>"
							href="#">
								<i class="icon-edit"></i> <?php echo Text::_('RST_TICKET_EDIT_MESSAGE'); ?>
							</a>
						<?php
						}

						if ($this->canDeleteMessage($message))
						{
							?>
							<a class="btn"
							onclick="return confirm(Joomla.JText._('RST_DELETE_TICKET_MESSAGE_CONFIRM'));" href="<?php echo JRoute::_('index.php?option=com_rsticketspro&task=ticketmessages.delete&cid='.$message->id . '&ticket_id=' . $message->ticket_id . '&' . JSession::getFormToken() . '=1'); ?>">
								<i class="icon-delete"></i>
								<?php echo Text::_('RST_TICKET_DELETE_MESSAGE'); ?>
							</a>
						<?php
						}
						?>
					</div>
				<?php
				}
				?>
			</div>
		</div>
<?php
	}
}
