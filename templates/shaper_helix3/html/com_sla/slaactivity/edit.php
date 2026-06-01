<?php
/**
 * @package    Sla
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('jquery.token');

$options['relative'] = true;
HTMLHelper::_('script', 'com_timelog/timelog.js', $options);
HTMLHelper::_('script', 'com_sla/slaActivity.js', $options);
Factory::getDocument()->addScript(Uri::root() . 'media/system/js/messages.min.js');

$app = Factory::getApplication();
$tmpl      = $app->input->getString('tmpl', '');
$licenseId = $app->input->getInt('licence_id', 0);

// Check template component set or not.
if (!empty($tmpl))
{
	$doc = Factory::getDocument();
	$appendUrl .= '&tmpl=' . $tmpl;
	$doc->addStyleSheet('templates/shaper_helix3/css/bootstrap.min.css');
	$doc->addStyleSheet('templates/shaper_helix3/css/custom.css');
}

Factory::getDocument()->addScriptDeclaration('
	Joomla.submitbutton = function(task)
	{
		if (task == "slaactivity.cancel" || document.formvalidator.isValid(document.getElementById("adminForm")))
		{
			jQuery("#permissions-sliders select").attr("disabled", "disabled");
			Joomla.submitform(task, document.getElementById("adminForm"));
		}
		else
		{
			jQuery("html, body").animate({ scrollTop: 0 }, "slow");
		}
	};
');

$slaActivityFormLink = 'index.php?option=com_sla&view=slaactivity&layout=edit';
$addSlaActivityLink = Route::_($slaActivityFormLink . '&tmpl=component&licence_id=' . $licenseId);

?>
<div>
	<div class="">
		<div class="">
			<div class="">
				<div class="timelog-add-form activity-edit front-end-edit ml-20 mr-20">
					<?php if (!$this->canCreateSlaActivity) : ?>
						<h3>
							<?php throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403); ?>
						</h3>
					<?php endif; ?>
					<button type="button" class="close" onclick="timeLog.closePopup();">&times;</button>
					<h3 class="activity-header">
						<?php
							echo (empty($this->item->id)) ? Text::_('COM_SLA_ADD_SLA_ACTIVITY') : Text::_('COM_SLA_EDIT_SLA_ACTIVITY');
						?>

						<!-- Add more button commented -->
						<?php
						/*
						if (!empty($this->item->id))
						{
						?>
						<a style="margin-right: 20px;" class="btn btn-primary btn-small pull-right" href="<?php echo $addSlaActivityLink; ?>">
							<i class="icon-plus"></i>
							<?php echo Text::_('COM_SLA_ADD_MORE_SLA_ACTIVITY'); ?>
						</a>
						<?php
						}
						*/
						?>

					</h3>
					<div class="clearfix"></div>
					<form id="adminForm" action="" method="post" class="form-validate form-horizontal ticketPopupForm" enctype="multipart/form-data">

						<?php
							echo $this->form->renderField('license_id');

							echo $this->form->renderField('lead_consultant_id');

							echo $this->form->renderField('sla_activity_type_id');

							echo $this->form->renderField('cluster_user');

							echo $this->form->renderField('activity_name');
							// echo $this->form->renderField('ideal_time');
							// echo $this->form->renderField('start_date');

							echo $this->form->renderField('due_date');
							echo $this->form->renderField('activity_desc');

							echo $this->form->renderField('todo_id');
						?>

						<div class="control-group pull-right" >
							<div class="controls pull-right">
								<button onclick="Joomla.submitbutton('slaactivity.save');" type="button" class="btn btn-primary"><?php echo Text::_('JSUBMIT'); ?></button>
								<a onclick="timeLog.closePopup();" class="btn btn-default" href="javascript:void(0);" title="<?php echo Text::_('JCANCEL'); ?>"><?php echo Text::_('JCANCEL'); ?></a>
							</div>
						</div>

						<input type="hidden" name="jform[id]" id="id" value="<?php echo $this->item->id; ?>" />
						<input type="hidden" name="option" value="com_sla"/>
						<input type="hidden" name="task" value="slaactivity.save"/>
						<input type="hidden" name="cluster_userId" id="cluster_userId" value="<?php echo $this->item->cluster_user;?>"/>

						<input type="hidden" name="cluster_lead_consultant_id" id="cluster_lead_consultant_id" value="<?php echo  $this->item->lead_consultant_id;?>"/>

						<input type="hidden" name="jform[prev_due_date]" id="prev_due_date" value="<?php echo HTMLHelper::_('date', $this->item->due_date, Text::_('COM_SLA_ACTIVITY_DATE_FORMAT'), false);?>"/>

						<?php echo HTMLHelper::_('form.token'); ?>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
jQuery(document).ready (function()
{
	jQuery('.btn-primary').click(function() {
		if (jQuery('.form-control-feedback').length < 1)
		{
			    jQuery('.btn-primary').prop('disabled',true);

		}
})
	// <!-- Close popup if data saved successfully -->

	if(jQuery('#system-message div').hasClass('alert alert-success'))
	{
		jQuery("#system-message-container").fadeTo(2000, 500, function(){
			window.parent.document.location.reload(true);
			window.parent.SqueezeBox.close();
		});
	}

	/* This function to get respective users via ajax */
	var clusterClient = jQuery('.cluster-client');
	clusterClient.change(function(){
		var dataFields = {licenseId: jQuery(this).val(), userId: jQuery("#cluster_userId").val(), LeadConsultantId: jQuery("#cluster_lead_consultant_id").val()};
		slaActivity.setuser(dataFields);
		slaActivity.setLeadConsultant(dataFields);
	});

	if (clusterClient.val())
	{
		var dataFields = {licenseId: clusterClient.val(), userId: jQuery("#cluster_userId").val(), LeadConsultantId: jQuery("#cluster_lead_consultant_id").val()};
		slaActivity.setuser(dataFields);
		slaActivity.setLeadConsultant(dataFields);
	}
});
</script>
