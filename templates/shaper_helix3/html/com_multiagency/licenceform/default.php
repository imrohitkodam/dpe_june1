<?php
/**
* @package    Com_Multiagency
*
* @author      Techjoomla <extensions@techjoomla.com>
* @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
* @license     GNU General Public License version 2 or later; see LICENSE.txt
*/

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;

JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', 'select');

// Load admin language file
$lang = Factory::getLanguage();
$lang->load('com_multiagency', JPATH_SITE);
$UriRoot = Uri::root();
$doc = Factory::getDocument();
$params        = ComponentHelper::getParams('com_dpe');
$dpeSelectedAdmins = $params->get("sladpeadmin");
$dpeSelectedAdmins = json_encode($dpeSelectedAdmins);

HTMLHelper::_('script', '/media/system/js/messages.min.js');
$doc->addScript(Uri::base() . '/media/com_multiagency/js/licence.min.js');
HTMLHelper::_('script', 'media/com_dpe/js/dpe.min.js');
HTMLHelper::_('script', 'media/com_tjcertificate/vendors/loader/js/loadingoverlay.min.js');
HTMLHelper::_('script', 'media/com_sla/js/sla.min.js');
HTMLHelper::_('script', 'media/com_sla/js/slaService.min.js');
HTMLHelper::_('script', 'media/com_sla/js/slatools.min.js');
HTMLHelper::_('jquery.token');

$user    = Factory::getUser();

$canEdit    = $user->authorise('core.edit', 'com_multiagency');
Text::script('COM_MULTIAGENCY_LICENCES_TOTAL_SEATS_ERROR');
Text::script('COM_MULTIAGENCY_LICENCES_TOTAL_SEATS_NEGATIVE_ERROR');
Text::script('COM_MULTIAGENCY_LICENCES_START_DATE_ERROR');
Text::script('COM_MULTIAGENCY_LICENCES_END_DATE_ERROR');
Text::script('COM_MULTIAGENCY_LICENCES_END_START_DATE_ERROR');
Text::script('COM_MULTIAGENCY_LICENCES_START_END_DATE_ERROR');
Text::script('COM_MULTIAGENCY_COURSE_SELECT_ERROR');
Text::script('COM_MULTIAGENCY_LICENCES_INVALID_DATE');
Text::sprintf('COM_MULTIAGENCY_ALREADY_PRESENT_EDIT_ERROR', Text::_('COM_MULTIAGENCY_ORGANISATION'));

$redirectUrl = 'index.php?option=com_dpe&view=schools';
$dpeUtility     = DPE::utilities();
$dpeUtility->getLanguageConstant();
$itemId         = $dpeUtility->getItemId($redirectUrl);

$activityLimit      = ComponentHelper::getParams('com_sla')->get('activityLimit');
$activityMsg        = Text::sprintf('COM_SLA_VALIDATE_ACTIVITY_COUNT', $activityLimit);
$licenceLimit       = $this->params->get('multliyear_licence_limit');
$licenceLimitMsg    = Text::sprintf('COM_MULTIAGENCY_LICENCE_LIMIT_MESSAGE', $licenceLimit);
$minLicenceLimit    = $this->params->get('min_licence_limit');
$minLicenceLimitMsg = Text::sprintf('COM_MULTIAGENCY_LICENCE_MIN_LIMIT_MESSAGE', $minLicenceLimit);
Text::script('COM_MULTIAGENCY_LICENCES_END_CURRENT_DATE_BIGGER_WARNING');
Text::script('COM_MULTIAGENCY_LICENCES_DURATION_MESSAGE');

$allLicences = array();

// calculate the contract number

$liceneState = $this->item->state;
$parentId = $this->item->parent_id ? $this->item->parent_id : $this->item->id;

JLoader::register('MultiagencyModelLicences', JPATH_ADMINISTRATOR . '/components/com_multiagency/models/licences.php');
$multiagencyModelLicences = BaseDatabaseModel::getInstance('Licences', 'MultiagencyModel');

// Get active(1), archive(2), upcoming(3) licence of current org 
$multiagencyModelLicences->setState('filter.state', array(1,2,3));
$multiagencyModelLicences->setState('filter.multiagency_id', $this->item->multiagency_id);
$multiagencyModelLicences->setState('filter.parent_id', $parentId);
$multiagencyModelLicences->setState('filter.id', $parentId);
$allLicences = $multiagencyModelLicences->getItems();

// Execute following if multiple licence and licence is active
if (count($allLicences) > 1)
{
	JLoader::import('components.com_dpe.includes.dpe', JPATH_SITE);

	// Get all licence ids
	$allLicences = array_column($allLicences, 'id');

	// Sort licence ids in ascending order
	sort($allLicences, SORT_NUMERIC);
	$totalLicences         = count($allLicences);
	$currentContractNumber =  DPE::utilities()->addOrdinalNumberSuffix(array_search($this->item->id, $allLicences) + 1);
}
?>

<script type="text/javascript">

var errorMsg           = '<?php echo $activityMsg; ?>';
var activityLimit      = '<?php echo $activityLimit; ?>';
var licenceLimit       = '<?php echo $licenceLimit; ?>';
var licenceLimitMsg    = '<?php echo $licenceLimitMsg; ?>';
var minLicenceLimit    = '<?php echo $minLicenceLimit; ?>';
var minLicenceLimitMsg = '<?php echo $minLicenceLimitMsg; ?>';

// Following code is commented beacuse in DPE we are not using course id for licence 
/*
function validateLicences()
{
	var promise = validLicence();

	var a = document.formvalidator.isValid(document.id('adminForm'));

	promise.fail(function(response)
	{

	}).done(function(response)
	{
		if (response.data.id == null)
		{
			Joomla.submitform('licenceform.save');
		}
		else
		{
			if (response.data.type === 'all')
			{
				alert("<?php echo Text::sprintf('COM_MULTIAGENCY_ALL_ALREADY_PRESENT_EDIT_ERROR', Text::_('COM_MULTIAGENCY_ORGANISATION')); ?>");

			}

			if(response.data.type === 'per')
			{
				alert("<?php echo Text::sprintf('COM_MULTIAGENCY_ALREADY_PRESENT_EDIT_ERROR', Text::_('COM_MULTIAGENCY_ORGANISATION')); ?>");
				return false;
			}

			return false;
		}
	});
}


function validLicence()
{
	var urlpath = Joomla.getOptions('system.paths').root + '/index.php?option=com_multiagency&task=licenceform.checkcourse';
	var userId = jQuery("#jform_multiagency_id").val();
	var licenceType = jQuery("#jform_licence_type").val();

	if (licenceType === 'all')
	{
		var courseId = 0;
	}
	else
	{
		var courseId = jQuery("#jform_course_id").val();
	}

	return jQuery.ajax({
		url: urlpath,
		type: 'post',
		data : {'userId' : userId,'courseId': courseId},
		dataType: 'json',
	});
}
*/

jQuery( document ).ready(function() { 

	// below code is show dpeadmin as per config 
	if (jQuery("#jform_notify_dpe_admin").is(":checked"))
	{
		jQuery("#licenceformdpeadmins").removeClass("hide");

		var dpeAdminId = '<?php echo $dpeSelectedAdmins;?>';
		dpeAdminId = dpeAdminId.split(','); 
		var dpeAdminIds = [];
	
		jQuery.map(dpeAdminId, function(value, key){
   				dpeAdminIds[key] = value.replace(/\D/g,''); 
		})

		if (jQuery('#id').val()==0)
		{
			jQuery("#jform_dpeadmins").val(dpeAdminIds); 
			jQuery("#jform_dpeadmins").trigger("chosen:updated");
		}
		
		
	}
	// End

	if (jQuery("#jform_notify_dpe_admin").is(":checked"))
	{

	}

jQuery("#jform_notify_dpe_admin").click(function ()
{
	if (jQuery(this).is(":checked"))
	{ 
		jQuery("#licenceformdpeadmins").removeClass("hide");

	}
	else
	{
		jQuery("#licenceformdpeadmins").addClass("hide");
	}
});

	var licenceType =  jQuery('#jform_licence_type').val();

	//~ if (licenceType === 'all')
	//~ {
	      //~ jQuery('.courseList').addClass('hide');
	      //~ jQuery('#jform_course_id').removeAttr('required');
	      //~ jQuery('#jform_course_id').removeClass('required');
	//~ }

	jQuery('#jform_multiagency_id').on('change', function() {
		jQuery('#jform_multiagency_id').prop("selected", false).trigger("liszt:updated");
		var urlpath = Joomla.getOptions('system.paths').root + '/index.php?option=com_multiagency&task=licenceform.getNotAssignCourse';
		var multiagencyId = jQuery("#jform_multiagency_id").val();
		return jQuery.ajax({
			url: urlpath,
			type: 'post',
			data : {'multiagencyId' : multiagencyId},
			dataType: 'json',
			success: function(data)
			{
				jQuery("#jform_course_id").find('option').remove().end().append(data);
				jQuery("#jform_course_id").trigger("chosen:updated");
			}
	});
	});
	jQuery('input[name="jform[use_tags]"]').on('change', function() {
		var useTags = jQuery('input[name="jform[use_tags]"]:checked').val();
		if (useTags == '0') { // Yes
			jQuery('#jform_tags').closest('.control-group').removeClass('hide');
			jQuery('#jform_multiagency_id').closest('.control-group').addClass('hide');
		} else { // No
			jQuery('#jform_tags').closest('.control-group').addClass('hide');
			jQuery('#jform_multiagency_id').closest('.control-group').removeClass('hide');
		}
	});

	if (jQuery('input[name="jform[use_tags]"]').length) {
		jQuery('input[name="jform[use_tags]"]:checked').trigger('change');
	}
});
</script>

<div class="licence-edit front-end-edit">
		<?php if (!$canEdit) : ?>
			<h3><?php throw new Exception(Text::_('COM_MULTIAGENCY_ERROR_MESSAGE_NOT_AUTHORISED'), 403); ?></h3>
		<?php else : ?>
		<?php if (!empty($this->item->id)): ?>
		<h1 class="fs-24 font-600 mt-0 mb-30" itemprop="name">

			<?php if ($this->item->state == 1) 
			{
				echo Text::_('COM_MULTIAGENCY_EDIT_ITEM_TITLE_LICENCES');
			}
			else
			{
				echo Text::_('COM_MULTIAGENCY_VIEW_ITEM_TITLE_LICENCES');
			}
			?>

			<?php 
			if ($currentContractNumber) 
			{

				if ($liceneState == 1)
				{
					$stateName = Text::_('COM_DPE_GENERICSTATUS_ACTIVE_STATUS_OPTION');
				}
				elseif ($liceneState == 2)
				{
					$stateName = Text::_('COM_DPE_GENERICSTATUS_ARCHIVED_STATUS_OPTION');
				}
				elseif ($liceneState == 3)
				{
					$stateName = Text::_('COM_DPE_GENERICSTATUS_UPCOMING_STATUS_OPTION');
				}

				echo Text::sprintf('COM_MULTIAGECNY_CONTRACT_NUMBER_MESSAGE', $stateName, $currentContractNumber, $totalLicences);
			}
			?>
		</h1>
		<?php else: ?>
		<h1 class="fs-24 font-600 mt-0 mb-30" itemprop="name">
			<?php echo Text::_('COM_MULTIAGENCY_ADD_ITEM_LICENCE'); ?>
		</h1>
		<?php endif; ?>
	<div class="row">
		<div class="col-sm-8 ucm-form-styling calendar-icon">
					<form id="adminForm" action="" class="form-validate form-horizontal add-licence" enctype="multipart/form-data">

						<input type="hidden" id="id" name="jform[id]" value="<?php echo $this->item->id; ?>" />
						<input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering; ?>" />
						<input type="hidden" name="jform[state]" value="<?php echo $this->item->state; ?>" />
						<input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
						<input type="hidden" name="jform[checked_out_time]" value="<?php echo $this->item->checked_out_time; ?>" />
						<?php echo $this->form->getInput('created_by'); ?>
						<?php echo $this->form->getInput('modified_by');
						if (empty($this->item->id))
						{
						?>
								
						<?php echo $this->form->renderField('tags'); ?>

						<?php echo $this->form->renderField('multiagency_id'); ?>
						<!--
							<?php echo $this->form->renderField('licence_type'); ?>

							<div class="courseList hide">
								<?php echo $this->form->renderField('course_id'); ?>
							</div>
						-->
						<?php
						}
						else
						{
							JLoader::register('MultiagencyFrontendHelpers', JPATH_SITE . '/components/com_multiagency/helpers/multiagency.php');
							$multiagencyHelper = new MultiagencyFrontendHelpers;

							JLoader::register('TjlmsCoursesHelper', JPATH_SITE . '/components/com_tjlms/helpers/courses.php');
							$tjlmsCoursesHelper = new TjlmsCoursesHelper;
							$courseInfo   = $tjlmsCoursesHelper->getCourseColumn($this->item->course_id, array('title'));
							?>
							<div class="control-group">
								<div class="control-label">
									<?php echo $this->form->getLabel('multiagency_id'); ?>
								</div>
								<div class="controls">
									<input type="text" class="form-control" disabled value="<?php echo $multiagencyHelper->getmultiagency($this->item->multiagency_id);?>">
								</div>
							</div>
<!--
							<div class="control-group">
								<div class="control-label">
									<?php //echo $this->form->getLabel('licence_type'); ?>
								</div>
								<div class="controls">
									<input type="text" class="form-control" disabled value="<?php //echo $this->item->type;?>">
								</div>
							</div>

                        	<div class="control-group <?php echo ($this->item->type == strtolower(Text::_('COM_MULTIAGENCY_LICENCE_TYPE_ALL'))) ? 'hide':''; ?>">
								<div class="control-label">
									<?php //echo $this->form->getLabel('course_id'); ?>
								</div>
								<div class="controls">
									<input type="text" class="form-control" disabled value="<?php //echo $courseInfo->title;?>">
								</div>
							</div>
-->

							<input type="hidden" value="<?php echo $this->item->type;?>" name="jform[licence_type]"/>
							<input type="hidden" value="<?php echo $this->item->multiagency_id;?>" name="jform[multiagency_id]"/>
							<input type="hidden" value="<?php echo $this->item->course_id;?>" name="jform[course_id]"/>
							<?php
						}

						//~ echo $this->form->renderField('total_seats');
					?>


						<?php
							if (empty ($this->item->id))
							{
								echo $this->form->renderField('use_tags');

								echo $this->form->renderField('show_in_sla_list');?>
								<div id="licenceFormNewSlaName" class="hide"><?php echo $this->form->renderField('new_sla'); ?></div>
								<?php
							}
						
							echo $this->form->renderField('sla_id');
						?>
						<?php
						if ($this->item->id)
						{
							// Showed saved type and count
							$slaModel = SlaFactory::model('Sla', array('ignore_request' => true));
							echo $slaModel->getSavedSlaToolsHtml($this->item->id, $this->form->getValue('sla_id'));
							
							echo $slaModel->getSavedSlaActivityTypeHtml($this->item->id, $this->form->getValue('sla_id'), false);
						}
						?>
						<div class="load-tools"></div>
						<div class="load-types"></div>

						<?php
							//echo $this->form->renderField('lead_consultant_id');
							echo $this->form->renderField('notify_dpe_admin');
						?>
						<div id="licenceformdpeadmins" class="control-group <?php echo (empty(json_decode($this->item->dpeadmins)) && !($this->item->notify_dpe_admin)) ? 'hide' : ''; ?>">
							<div class="control-label">
								<?php echo $this->form->getLabel('dpeadmins'); ?>
							</div>
							<div class="controls">
								<?php echo $this->form->getInput('dpeadmins'); ?>
							</div>
						</div>
						<?php
						if ($this->item->time_measure && $this->item->duration)
						{?>
							<div class="control-group">
								<div class="control-label">
									<?php echo $this->form->getLabel('time_measure'); ?>
								</div>
								<div class="controls">
									<input type="text" class="form-control" disabled value="<?php echo ucfirst($this->item->time_measure);?>">
								</div>
							</div>
							<div class="control-group">
								<div class="control-label">
									<?php echo $this->form->getLabel('duration'); ?>
								</div>
								<div class="controls">
									<input type="text" class="form-control" disabled value="<?php echo $this->item->duration;?>">
								</div>
							</div>
						<?php
						}
						?>
						<?php if (!empty($this->item->id)) {  ?>
							<div class="control-group">
								<div class="control-label">
									<?php echo $this->form->getLabel('used_seats'); ?>
								</div>
								<div class="controls">
									<input type="text" class="form-control" disabled value="<?php echo $this->item->used_seats;?>">
								</div>
							</div>
						<?php } ?>

						<!-- Multiyear fields -->
						<?php if (empty ($this->item->id)) { ?>
							<?php echo $this->form->renderField('multiyearlicence'); ?>
							<?php echo $this->form->renderField('time_measure'); ?>
							<?php echo $this->form->renderField('duration'); ?>
							<?php echo $this->form->renderField('multiple_count'); ?>
						<?php } ?>

						<?php echo $this->form->renderField('start_date'); ?>
						<?php echo $this->form->renderField('end_date'); ?>
						<?php echo $this->form->renderField('comment'); ?>
					

						<?php if (!$user->authorise('core.admin','multiagency')): ?>
							<script type="text/javascript">
								jQuery.noConflict();
								jQuery('.tab-pane select').each(function(){
									var option_selected = jQuery(this).find(':selected');
									var input = document.createElement("input");
									input.setAttribute("type", "hidden");
									input.setAttribute("name", jQuery(this).attr('name'));
									input.setAttribute("value", option_selected.val());
									document.getElementById("form-licence").appendChild(input);
								});
							</script>
						<?php endif; ?>

						<div class="control-group text-right float-end mt-3">
							<div class="controls">
						<!-- Don't show edit button upcoming and archive licence -->
						<?php if ($this->item->state != 2 && $this->item->state != 3) { ?>
								<?php if ($this->canSave): ?>
									<button type="button" id="save_licence" class="licenceform validate btn btn-primary" onclick="licence.save();"><?php echo Text::_('JSUBMIT'); ?></button>
								<?php endif; ?>
						<?php } ?>
								<a class="btn btn-default" href="<?php echo Route::_($redirectUrl . '&Itemid=' . $itemId, false); ?>" title="<?php echo Text::_('JCANCEL'); ?>"><?php echo Text::_('JCANCEL'); ?></a>
							</div>
						</div>

						<input type="hidden" value="all" name="jform[licence_type]"/>
						<input type="hidden" value="0" name="jform[total_seats]"/>
						<input type="hidden" name="option" value="com_multiagency"/>
						<input type="hidden" name="task" value="licenceform.save"/>
						<input type="hidden" name="redirectUrl" value="<?php echo Route::_($redirectUrl . '&Itemid=' . $itemId, false); ?>"/>

						<?php echo HTMLHelper::_('form.token'); ?>
					</form>
				<?php endif; ?>
		</div>
	<div class="col-sm-4 licenceinfo-template-html"></div>
	</div>
</div>
