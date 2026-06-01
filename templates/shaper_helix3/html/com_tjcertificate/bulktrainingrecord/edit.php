<?php
/**
 * @package     TJCertificate
 * @subpackage  com_tjcertificate
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('bootstrap.renderModal');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('bootstrap.framework');
HTMLHelper::StyleSheet('media/com_tjcertificate/css/tjCertificate.css');
HTMLHelper::_('jquery.token');
HTMLHelper::_('formbehavior.chosen', 'select');

$options['relative'] = true;
HTMLHelper::_('script', 'media/com_tjcertificate/vendors/loader/js/loadingoverlay.min.js');
HTMLHelper::_('script', 'com_tjcertificate/tjCertificateService.min.js', $options);
HTMLHelper::_('script', 'com_tjcertificate/certificate.min.js', $options);
HTMLHelper::script(Uri::root() . 'media/system/js/messages.min.js');

$userLimit = $this->params->get('users_select_limit');
$message = Text::sprintf("COM_TJCERTIFICATE_USER_LIMIT_MESSAGE", $userLimit);

// Check elearning tool action for DPE
if (!$this->user->authorise('core.manageall', 'com_cluster'))
{
	JLoader::import('/components/com_subusers/includes/rbacl', JPATH_ADMINISTRATOR);

	if (!RBACL::check($this->user->id, 'com_cluster', 'core.viewShika', 'com_tjlms'))
	{
		$app = Factory::getApplication();

		$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'));

		return;
	}
}

?>
<form action="" class="form-validate form-horizontal add-records ucm-form-styling"
    method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
	<div class="container-fluid">
		<div class="row">
			<div class="col-sm-6">
					<h3 class="header mb-3">
						<?php
							echo Text::_('COM_TJCERTIFICATE_ADD_EXTERNAL_CERTIFICATES');
						?>
					</h3>
				<?php
					// Check agency is enabled
					if ($this->isAgencyEnabled)
					{
						echo $this->form->renderField('agency_id');
					}

					echo $this->form->renderField('assigned_user_id');
					echo $this->form->renderField('name');
					echo $this->form->renderField('issuing_org');
					echo $this->form->renderField('issued_on');
					echo $this->form->renderField('expired_on');
					echo $this->form->renderField('status');
					echo $this->form->renderField('state');
					?>
					<!-- hidden field is added to take care saving an unchecked checkbox from a form -->
					<input type="hidden" name="jform[notify_users]" value="0">
					<?php
					echo $this->form->renderField('notify_users');
					echo $this->form->renderField('created_by', null, null, ['class' => 'hidden']);
				?>
			</div>
		</div>
	</div>	

	<div class="control-group">
		<div class="col-md-6 col-sm-6">
			<div class="pull-right">
				<button onclick="certificate.addRecords()" type="button" class="btn btn-primary"><?php echo Text::_('JSUBMIT'); ?></button>
				<button type="button" class="btn btn-default"  onclick="Joomla.submitbutton('trainingrecord.cancel')">
						<span><?php echo Text::_('JCANCEL'); ?></span>
				</button>
			</div>
		</div>
	</div>
	<input type="hidden" name="option" value="com_tjcertificate"/>
	<input type="hidden" name="task" value="bulktrainingrecord.save"/>
	<input type="hidden" name="site" value="f"/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
<script type="text/javascript">

	var userLimit = '<?php echo $userLimit;?>';
	var message = '<?php echo $message;?>';

	jQuery("#jform_assigned_user_id").chosen({max_selected_options: userLimit});
	jQuery("#jform_assigned_user_id").bind("liszt:maxselected", function () {
		Joomla.renderMessages({'error': [message] });
	});
	
	jQuery(document).ready(function(){
		
       jQuery('#jform_assigned_user_id_chosen').last().hide()

    })

</script>
