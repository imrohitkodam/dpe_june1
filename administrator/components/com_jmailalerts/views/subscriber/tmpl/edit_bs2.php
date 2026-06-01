<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
?>

<?php
// Add Javascript
$doc = Factory::getDocument();
$doc->addScriptDeclaration("
	jQuery(document).ready(function(){
		var userid = jQuery('#jform_user_id').val();
		CheckBoxCheck(userid);
	});

	Joomla.submitbutton = function(task) {
		if (task == 'subscriber.cancel') {
			Joomla.submitform(task, document.getElementById('subscriber-form'));
		}
		else {
			if (task != 'subscriber.cancel' && document.formvalidator.isValid(document.getElementById('subscriber-form'))) {
				Joomla.submitform(task, document.getElementById('subscriber-form'));
			}
			else {
				alert('" . $this->escape(Text::_('JGLOBAL_VALIDATION_FORM_FAILED')) . "');
			}
		}
	}

	/*Method guest check box is yes then userid=0 & it will be readonly*/
	function Guest_User_Check() {
		if (document.adminForm.gues_user_chk.checked === true) {
			jQuery('#jform_user_id').val('0');
			jQuery('#jform_user_id').attr('readonly', true);
		}
		else {
			jQuery('#jform_user_id').val('');
			jQuery('#jform_user_id').attr('readonly', false);
		}
	}

	/*Method used when editing guest user data. If user is guest user id=0 ,checkbox guest user should be check*/
	function CheckBoxCheck(userid) {
		if (userid == 0) {
			document.adminForm.gues_user_chk.checked=true;
			jQuery('#jform_user_id').val('0');
			jQuery('#jform_user_id').attr('readonly', true);
		}
		else {
			document.adminForm.gues_user_chk.checked = false;
			jQuery('#jform_user_id').attr('readonly', false);
		}
	}"
);
?>

<div class="<?php echo JMAILALERTS_WRAPPER_CLASS;?>" id="jmailalerts-subscriber">
	<form action="<?php echo Route::_('index.php?option=com_jmailalerts&layout=edit&id=' . (int) $this->item->id); ?>"
		method="post" enctype="multipart/form-data"
		name="adminForm" id="subscriber-form"
		class="form-validate">
		<div class="row-fluid">
			<div class="span10 form-horizontal">
				<fieldset class="adminform">
					<?php echo $this->form->renderField('id'); ?>
					<?php echo $this->form->renderField('state'); ?>

					<div class="control-group">
						<label class="control-label" for="gues_user_chk" title="<?php echo Text::_('COM_JMAILALERTS_GUEST_USER_SUBSCRIPTION_TOOLTIP');?>">
							<?php echo Text::_('COM_JMAILALERTS_GUEST_USER_SUBSCRIPTION');?>
						</label>
						<div class="controls">
							<input type="checkbox" name="gues_user_chk" id="gues_user_chk" value="1" onchange="Guest_User_Check()"/>
						</div>
					</div>

					<?php echo $this->form->renderField('user_id'); ?>
					<?php echo $this->form->renderField('name'); ?>
					<?php echo $this->form->renderField('email_id'); ?>
					<?php echo $this->form->renderField('alert_id'); ?>
					<?php echo $this->form->renderField('frequency'); ?>
				</fieldset>
			</div>
		</div>

		<input type="hidden" name="jform[date]" value="<?php echo $this->item->date; ?>" />
		<input type="hidden" name="jform[plugins_subscribed_to]" value="<?php echo $this->item->plugins_subscribed_to; ?>" />
		<input type="hidden" name="task" value="" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
