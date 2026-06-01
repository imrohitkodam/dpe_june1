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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

/*if ($this->ticket->status_id == RST_STATUS_CLOSED)
{
	?>
	<p><strong><?php echo Text::_('RST_TICKET_REPLIES_CLOSED'); ?></strong></p>

	<?php
	if ($this->allowVoting)
	{
		?>
		<p id="com-rsticketspro-rated-message">
			<?php echo Text::_($this->ticket->feedback ? ($this->isStaff ? 'RST_TICKET_FEEDBACK_SENT_STAFF' : 'RST_TICKET_FEEDBACK_SENT') : 'RST_TICKET_FEEDBACK'); ?>
		</p>
		<div id="star"></div>
	<?php
	}
}*/
if ($this->ticket->status_id != RST_STATUS_CLOSED)
{
	if ($this->canReply)
	{
		?>
		<script>
		function validateTicket()
		{
			var e = document.getElementById("ticketcustomer_id");
			var customerUser = e.options[e.selectedIndex].value;

			if (typeof customerUser === 'undefined' || customerUser === null || customerUser === '')
			{
				alert("<?php echo Text::_('RST_TICKET_REPLY_VALIDATE'); ?>");
			}
			else
			{
				document.getElementById("com-rsticketspro-reply-button").style.display = "none";
				RSTicketsPro.showReply(this);
			}
		}
		</script>
		<button type="button" class="btn btn-primary btn-large" id="com-rsticketspro-reply-button" onclick="validateTicket();">
			<?php echo Text::_('RST_TICKET_REPLY'); ?>
		</button>
		<div id="com-rsticketspro-reply-box" class="hidden">
			<h3 class="rst_heading"><?php echo Text::_('RST_REPLY_TO_TICKET'); ?></h3>
			<?php
				$this->field->startFieldset();

					if ($this->isStaff && $this->showSearch)
					{
						$this->field->showField($this->form->getLabel('search'), $this->form->getInput('search'));
					}

					$this->field->showField(
					$this->form->getLabel('message'), '<div class="rst_editor">' . $this->form->getInput('message') . '</div>',
					array('class' => 'clearfix')
					);

					if ($this->isStaff && $this->showSignature)
					{
						$signatureLink = '<label></label><p class="rst_editor"><small><a href="' . Route::_('index.php?option=com_rsticketspro&view=signature') . '">' . Text::_('RST_EDIT_SIGNATURE') . '</a></small></p>';
						$this->field->showField($this->form->getLabel('use_signature'), $this->form->getInput('use_signature') . $signatureLink);
					}

					if ($this->canUpload)
					{
						// Prepend the upload message
						$this->field->showField('', '', array('id' => 'rst_files_message_container'));
						$this->field->showField($this->form->getLabel('files'), $this->form->getInput('files'), array('id' => 'rst_files_container'));
					}

				$this->field->endFieldset();

				// DPE - Hack for DPE specific to hide consent
				$consent = $this->form->getInput('consent', array(), '1');
				$this->field->showField($this->form->getLabel('consent'), $consent, array('class' => 'hide'));
				?>
				<button type="button" onclick="Joomla.submitbutton('ticket.reply', this);" class="btn btn-primary float-end">
					<?php echo Text::_('RST_TICKET_SUBMIT'); ?>
				</button>
		</div>
	<?php
	}
}

if ($this->canUpload)
{
	?>
	<script type="text/javascript">
	RSTicketsPro.getDepartment = function() {
		return {
			id: <?php echo $this->ticket->department_id; ?>,
			uploads: {
				message: '<div class="rst_text"><?php echo addslashes($this->department->upload_message); ?></div>',
				max: <?php echo $this->department->upload_files; ?>
			}
		};
	}
	</script>
<?php
}

if ($this->allowVoting)
{
	$img_path = HTMLHelper::image('com_rsticketspro/raty/star-on.png', '', null, true, 1);
	$img_path = str_replace('star-on.png', '', $img_path);
?>
<script type="text/javascript">
jQuery(document).ready(function($){
	$('#star').raty({
		'path': '<?php echo $img_path ?>',
		'score': <?php echo $this->ticket->feedback ? $this->ticket->feedback : 'undefined'; ?>,
		'readOnly': <?php echo $this->isStaff || $this->ticket->feedback ? 'true' : 'false'; ?>,
		'hints': [
			'<?php echo Text::_('RST_FEEDBACK_1', true); ?>',
			'<?php echo Text::_('RST_FEEDBACK_2', true); ?>',
			'<?php echo Text::_('RST_FEEDBACK_3', true); ?>',
			'<?php echo Text::_('RST_FEEDBACK_4', true); ?>',
			'<?php echo Text::_('RST_FEEDBACK_5', true); ?>'
		],
		'click': function(score, evt) {
			$(this).raty('readOnly', true);
			$('#com-rsticketspro-rated-message').hide().html('<?php echo Text::_('RST_TICKET_FEEDBACK_SENT', true); ?>').fadeIn();
			RSTicketsPro.sendRating('<?php echo Uri::root(true);?>/index.php?option=com_rsticketspro', score, <?php echo $this->ticket->id;?>);
		}
	});
});
</script>
<?php
}

if ($this->isStaff && $this->showSearch)
{
	?>
<script type="text/javascript">
	RSTicketsPro.finishTypeAhead = function(params, $) {
		var id = params.id;
		$.post('index.php?option=com_rsticketspro', {
			'option': 'com_rsticketspro',
			'view': 'article',
			'format': 'json',
			'cid': id
		}, function(data) {
			<?php
			if (!$this->allowEditor)
			{
				?>
				$('#ticket_message').val(data.text);
			<?php
			}
			else
			{
				?>
				jInsertEditorText(data.text, 'ticket_message');
			<?php
			}
			?>
		});
	}
</script>
<?php
}

if ($this->form->getValue('message'))
{
	?>
	<script>
		jQuery(function($){
		   RSTicketsPro.showReply(document.getElementById('com-rsticketspro-reply-button'));
		});
	</script>
<?php
}
