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

JText::script('RST_MAX_UPLOAD_FILES_REACHED');
JText::script('RST_TICKET_ATTACHMENTS');
JText::script('RST_TICKET_ATTACHMENTS_REQUIRED');

$script = '';
foreach ($this->departments as $department)
{
	$upload = $department->upload ? 'true' : 'false';
	$uploadRequired = $department->upload_ticket_required ? 'true' : 'false';
	$uploadMessage = json_encode($department->upload_message);
	$uploadMessageMaxFiles = json_encode($department->upload_message_max_files);
	$uploadMessageMaxSize = json_encode($department->upload_message_max_size);

	$script .= "RSTicketsPro.departments[{$department->id}] = {
	id: {$department->id},
	priority: {$department->priority_id},
	uploads: {
		allowed: {$upload},
        required: {$uploadRequired},
		message: {$uploadMessage},
        message_max_files: {$uploadMessageMaxFiles},
        message_max_size: $uploadMessageMaxSize,
		max: {$department->upload_files}
	}
};";
}
$script .= "window.addEventListener('DOMContentLoaded', function() { RSTicketsPro.changeDepartment() });";

JFactory::getDocument()->addScriptDeclaration($script);

if ($this->globalMessage && $this->globalMessagePosition === 'top')
{
	?>
	<div class="rst-form-section">
		<?php echo $this->globalMessage; ?>
	</div>
	<?php
}

if ($this->submitMessage && $this->submitMessagePosition === 'top')
{
	?>
	<div class="rst-form-section">
		<?php echo $this->submitMessage; ?>
	</div>
	<?php
}
?>
<form action="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=submit'); ?>" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data" class="form-horizontal">
<?php
// only staff members with enough permissions
// can select existing users from the database
if ($this->canChangeSubmitType)
{
	echo $this->form->getField('submit_type')->renderField();
	echo $this->form->getField('customer_id')->renderField(array('class' => 'rst_customer_id_container'));

	// alternative email
	if ($this->showAltEmail)
	{
		echo $this->form->getField('alternative_email')->renderField(array('class' => 'rst_alt_email_container'));
	}
}
echo $this->form->getField('email')->renderField(array('class' => 'rst_email_container'));
echo $this->form->getField('name')->renderField(array('class' => 'rst_name_container'));

// alternative email
if ($this->showAltEmail)
{
	echo $this->form->getField('alternative_email')->renderField(array('class' => 'rst_alt_email_container'));
}

// department
echo $this->form->getField('department_id')->renderField(array('class' => 'rst_department_id_container'));

// append the custom fields after the department
foreach ($this->customFields as $customField)
{
	echo $customField;
}

// subject
echo $this->form->getField('subject')->renderField(array('class' => 'rst_subject_container'));

// message
echo $this->form->getField('message')->renderField(array('class' => 'rst_message_container'));

// priority
echo $this->form->getField('priority_id')->renderField(array('class' => 'rst_priority_id_container'));

// prepend the upload message
echo '<div id="rst_files_message_container"></div>';

// files
echo $this->form->getField('files')->renderField(array('class' => 'rst_files_container'));

if ($this->globalMessage && $this->globalMessagePosition === 'bottom')
{
	?>
	<div class="rst-form-section">
		<?php echo $this->globalMessage; ?>
	</div>
	<?php
}

if ($this->submitMessage && $this->submitMessagePosition === 'bottom')
{
	?>
	<div class="rst-form-section">
		<?php echo $this->submitMessage; ?>
	</div>
	<?php
}
?>
	<div>
		<button type="button" class="btn btn-success" onclick="Joomla.submitbutton('submit.save');"><?php echo JText::_('RST_SUBMIT'); ?></button>
		<button type="button" class="btn btn-secondary" onclick="Joomla.submitbutton('submit.cancel');"><?php echo JText::_('JCANCEL'); ?></button>
	</div>

	<?php echo JHtml::_('form.token'); ?>
	<input type="hidden" name="task" value="" />
</form>