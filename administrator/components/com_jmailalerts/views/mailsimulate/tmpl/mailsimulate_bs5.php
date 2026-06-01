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
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('bootstrap.framework');
HTMLHelper::_('bootstrap.modal');
?>

<?php
// Add Javascript
$doc = Factory::getDocument();
$doc->addScriptDeclaration("
	function validate_form(){
		// Chek if alert, userid and the email address is entered
		if(document.getElementById('user_id_box').value == '' ) {
			alert('" . Text::_('COM_JMAILALERTS_SIMULATE_VALIDATION_MSG') . "');
			return 0;
		}
		else {
			return 1;
		}
	}

	function submit_this_form(adminForm){
		adminForm.submit();
	}

	function previewMail()
	{
		let simulationLink = '" . Uri::base() . "index.php?option=com_jmailalerts&task=mailsimulate.simulate&tmpl=component&send_mail_to_box=admin@admin.com&flag=1&user_id_box=';
		let userid = document.getElementById('user_id_box').value;
		let sdate  = document.getElementById('select_date_box').value;
		let prev  = '1';

		let alertname  = document.getElementById('altypename').value;
		simulationLink = simulationLink + userid + '&select_date_box=' + sdate + '&prev='+ prev + '&altypename=' + alertname;

		jQuery('#previewModal').find('iframe').attr('src', simulationLink);
	}

	jQuery(document).ready(function()
	{
		ShowHidemailbox()
	});

	function ShowHidemailbox() {
		var status;
		status=jQuery('input:radio[name=\"show_mail_to_box\"]:checked').val();

		if (status == 1) {
			
			jQuery('.send_mail_to_box_div').show();
		}
		else {
			
			jQuery('.send_mail_to_box_div').hide();
		}
	}
"
);
?>

<div class="<?php echo JMAILALERTS_WRAPPER_CLASS; ?>" id="jmailalerts-mailsimulate">
	<form action="<?php echo Route::_('index.php?option=com_jmailalerts&view=mailsimulate'); ?>"
		method="POST" name="adminForm" ENCTYPE="multipart/form-data"
		id="adminForm" class="form-horizontal">

		<div id="j-main-container" class="j-main-container">
			<div class="row">
				<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
					<div class="control-group">
						<div class="control-label">
							<label for="altypename"><?php echo Text::_("COM_JMAILALERTS_SELECT_ATYPE"); ?></label>
						</div>
						<div class="controls"><?php echo $this->alertname; ?></div>
					</div>

					<div class="control-group">
						<div class="control-label">
							<label for="user_id_box"><?php echo Text::_("COM_JMAILALERTS_USER_ID"); ?></label>
						</div>
						<div class="controls">
							<input type="number" width="20" size="20" maxlength="20" value="" name = "user_id_box" id="user_id_box" class="form-control"/>
						</div>
					</div>
					<div class="control-group">
						<div class="control-label">
							<label for="send_mail_to_box"><?php echo Text::_("COM_JMAILALERTS_SEND_MAIL_TO_ADMIN"); ?></label>
						</div>
					
						<div class="controls ">
								<label class="radio inline">
									<input type="radio" class="btn-group" name="show_mail_to_box" id="show_mail_to_box1" value="1" onclick="ShowHidemailbox()"/>
									<?php echo Text::_('COM_JMAILALERTS_YES'); ?>
								</label>
								<label class="radio inline">
									<input type="radio" class="btn-group" name="show_mail_to_box" id="show_mail_to_box2" value="0" checked="checked" onclick="ShowHidemailbox()"/>
										<?php echo Text::_('COM_JMAILALERTS_NO'); ?>
								</label>
							</div>
					</div>
					<div class="control-group send_mail_to_box_div">
						<div class="control-label">
							<label for="send_mail_to_box"><?php echo Text::_("COM_JMAILALERTS_SEND_MAIL_TO"); ?></label>
						</div>
						<div class="controls">
							<input type="email" width="20" size="20" maxlength="40" value="" name = "send_mail_to_box" id="send_mail_to_box" class="form-control"/>
						</div>
					</div>

					<div class="control-group">
						<div class="control-label">
							<?php echo Text::_(''); ?>
							<label for="select_date_box"><?php echo Text::_("COM_JMAILALERTS_SELECT_DATE"); ?></label>
						</div>
						<div class="controls">
							<?php echo HTMLHelper::_(
								'calendar', date(''), 'select_date_box', 'select_date_box',
								'%Y-%m-%d ',
								array('class' => 'inputbox', 'size' => '20', 'maxlength' => '19', 'name' => 'select_date_box', 'id' => 'select_date_box')
							); ?>
						</div>
					</div>

					<div class="text text-center">
						<button type="button" class="btn btn-success" id="simulate_button"
							onclick=" if(validate_form()) { submit_this_form(this.form); }">
								<?php echo Text::_('COM_JMAILALERTS_SIMULATE'); ?>
						</button>

						&nbsp;&nbsp;&nbsp;

						<a id ="linkforsimulate"
							onclick="previewMail()"
							data-bs-toggle="modal"
							data-bs-target="#previewModal"
							href="javascript:void(0);"
							class="btn btn-info">
								<?php echo Text::_('COM_JMAILALERTS_PREVIEW'); ?>
						</a>

						<?php
						echo HTMLHelper::_(
							'bootstrap.renderModal',
							'previewModal',
							array(
								'url'         => Uri::base(),
								'title'       => Text::_('COM_JMAILALERTS_PREVIEW'),
								'height'      => '700px',
								'width'       => '600px',
								'bodyHeight'  => '70',
								'modalWidth'  => '80',
								'closeButton' => false,
								'backdrop'    => 'static',
								'keyboard'    => false,
								'footer'      => '<button class="btn btn-secondary" type="button" data-bs-dismiss="modal">' . Text::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>',
							)
						);
						?>
					</div>
				</div>
			</div>
		</div>
		<input type="hidden" name="task" value="mailsimulate.simulate" />
	</form>
</div>
