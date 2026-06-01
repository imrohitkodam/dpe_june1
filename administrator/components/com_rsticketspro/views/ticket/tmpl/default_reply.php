<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

JText::script('RST_TICKET_FEEDBACK_SENT');

$showReply = $this->form->getValue('message');

if ($this->ticket->status_id == RST_STATUS_CLOSED)
{
	?>
	<p><strong><?php echo JText::_('RST_TICKET_REPLIES_CLOSED'); ?></strong></p>
	<?php
	if ($this->allowVoting && (($this->isStaff && $this->ticket->feedback) || !$this->isStaff))
	{
		$ratyParams = array(
			'score' => $this->ticket->feedback ? $this->ticket->feedback : null,
			'readOnly' => $this->isStaff || $this->ticket->feedback,
			'hints' => array(
				JText::_('RST_FEEDBACK_1'),
				JText::_('RST_FEEDBACK_2'),
				JText::_('RST_FEEDBACK_3'),
				JText::_('RST_FEEDBACK_4'),
				JText::_('RST_FEEDBACK_5')
			)
		);

		$script = 'RSTicketsPro.initRaty(' . json_encode($ratyParams) . ')';

		JFactory::getDocument()->addScriptDeclaration($script);
		?>
		<p id="com-rsticketspro-rated-message"><?php echo JText::_($this->ticket->feedback ? ($this->isStaff ? 'RST_TICKET_FEEDBACK_SENT_STAFF' : 'RST_TICKET_FEEDBACK_SENT') : 'RST_TICKET_FEEDBACK'); ?></p>
		<div id="star"></div>
		<?php
	}
}
else
{
	if ($this->canReply)
	{
		if (!$showReply)
		{
			?>
			<p><button type="button" class="btn btn-primary btn-large" id="com-rsticketspro-reply-button" onclick="RSTicketsPro.showReply(this);"><?php echo JText::_('RST_TICKET_REPLY'); ?></button></p>
			<?php
		}
		?>
		<div id="com-rsticketspro-reply-box" <?php if (!$showReply) { ?>class="hidden"<?php } ?>>
			<h3 class="rst_heading"><?php echo JText::_('RST_REPLY_TO_TICKET'); ?></h3>
			<?php
			if ($this->isStaff && RSTicketsProHelper::getConfig('show_reply_as_customer'))
			{
				echo $this->form->getField('reply_as_customer')->renderField();
			}

			if ($this->isStaff && $this->showSearch)
			{
				echo $this->form->getField('search')->renderField();
			}

			echo $this->form->getField('message')->renderField();

			if ($this->isStaff && $this->showSignature)
			{
				echo $this->form->getField('use_signature')->renderField();

				echo '<p><small><a href="'.JRoute::_('index.php?option=com_rsticketspro&view=signature').'">'.JText::_('RST_EDIT_SIGNATURE').'</a></small></p>';
			}

			if ($this->canUpload)
			{
				$script = "RSTicketsPro.getDepartment = function() { return { id: {$this->ticket->department_id}, uploads: { max: {$this->department->upload_files} } }; }";
				JFactory::getDocument()->addScriptDeclaration($script);

				// prepend the upload message
				echo '<div id="rst_files_message_container">' . $this->department->upload_message . ' ' . $this->department->upload_message_max_files . ' ' . $this->department->upload_message_max_size . '</div>';
				echo $this->form->getField('files')->renderField(array('class' => 'rst_files_container'));
			}
			?>
			<p><button type="button" onclick="Joomla.submitbutton('ticket.reply')" class="btn btn-primary"><?php echo JText::_('RST_TICKET_SUBMIT'); ?></button></p>

			<hr />
		</div>
	<?php
	}
}
