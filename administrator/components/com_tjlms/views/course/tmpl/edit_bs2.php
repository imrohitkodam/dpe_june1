<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla extensions@techjoomla.com
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('behavior.tooltip');
HTMLHelper::_('behavior.formvalidation');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('formbehavior.chosen', '#jform_catid', null, array('disable_search_threshold' => 0 ));

jimport('joomla.filesystem.file');

if (JVERSION >= '3.0')
{
	HTMLHelper::_('bootstrap.tooltip');
	HTMLHelper::_('formbehavior.chosen', 'select');
	HTMLHelper::_('behavior.multiselect');
}

include_once JPATH_COMPONENT . '/js_defines.php';

$allow_paid_courses = $this->tjlmsparams->get('allow_paid_courses', '0', 'INT');

// Import helper for declaring language constant
JLoader::import('TjlmsHelper', JPATH_ROOT . '/administrator/components/com_tjlms/helpers/tjlms.php');

// Call helper function
TjlmsHelper::getLanguageConstant();

?>

<script type="text/javascript">

	jQuery(window).load(function() {

		var certReq= jQuery("#jform_certificate_term").val();
		if(certReq==1 || certReq==2){
				jQuery('#jform_certificate_id-lbl').append('<span id="add_star" class="star">&nbsp;*</span>');
				jQuery('#jform_certificate_id').addClass('required');
		}

		jQuery(document.body).on('change',"#jform_certificate_term",function (e) {
			jQuery('#add_star').remove();
			var certReq= jQuery("#jform_certificate_term option:selected").val();
			if(certReq==1 || certReq==2){
				jQuery('#jform_certificate_id-lbl').append('<span id="add_star" class="star">&nbsp;*</span>');
				jQuery('#jform_certificate_id').addClass('required');
			}else{
				jQuery('#add_star').remove();
				jQuery('#jform_certificate_id').removeClass('required');
			}
		});

		var current_entered_char = jQuery("#jform_short_desc").val().length;
		var characters = lesson_characters_allowed;

		var sht_desc_length_onload = characters - current_entered_char;

		jQuery( '#max_body1' ).html(sht_desc_length_onload);

		jQuery("#jform_title").blur(function(){
				jQuery(this).val(jQuery.trim(jQuery(this).val()));
		});

		jQuery("#jform_short_desc").blur(function(){
				jQuery(this).val(jQuery.trim(jQuery(this).val()));
		});

		jQuery(document).on('change', 'input[name="jform[type]"]:checked', '#course-form',function (e) {
				var courseType = jQuery('input[name="jform[type]"]:checked', '#course-form').val();
				if (courseType == 1)
				{
					jQuery('.planName').addClass('required');
					jQuery('.subs_plan_price').addClass('required');
				}
				else
				{
					jQuery('.planName').removeClass('required');
					jQuery('.subs_plan_price').removeClass('required');
				}
			});

	});

	Joomla.submitbutton = function(task)
	{
		if (task == 'course.cancel') {
			Joomla.submitform(task, document.getElementById('course-form'));
		}
		else
		{
			var courseTitle = jQuery.trim(jQuery('#jform_title').val());
			var rex         = /(<([^>]+)>)/ig;
			var courseName  = courseTitle.replace(rex , "");
			jQuery('#jform_title').val(courseName);

			var courseType  = jQuery('input[name="jform[type]"]:checked', '#course-form').val();
			var duration    = jQuery('.subs_plan_duration').val();
			var timeMeasure = jQuery('.timeMeasure').val();

			if (courseType == 1 && duration == 0 && timeMeasure != "unlimited")
			{
				enqueueSystemMessage("<?php	echo JText::_('COM_TJLMS_SUBSCRIPTION_PLAN_INVALID_DURATION');	?>",".admin.com_tjlms.view-course");

				return false;
			}

			if (courseType==1)
			{
				jQuery('.planName').addClass('required');
				jQuery('.subs_plan_price').addClass('required');
			}

			if (document.formvalidator.isValid(document.id('course-form')))
			{
				//var ispaid = jQuery('.lms_course_type').val();

				var ispaid = jQuery('input[name="jform[type]"]:checked', '#course-form').val();

				//check date
				<?php if (empty($this->item->id)) : ?>

					var selectedDate = jQuery('#jform_start_date').val();
					courseStartDate = new Date(selectedDate);
					courseStartDate.setHours(0, 0, 0, 0);

				<?php endif; ?>

				if (ispaid == 1)
				{
					// If course is guest then it can be paid course
					var accessLevel = jQuery('#jform_access').val();

					if (accessLevel == 5)
					{
						enqueueSystemMessage("<?php	echo JText::_('COM_TJLMS_GUESTCOURSE_PAID_VALIDATION');	?>",".admin.com_tjlms.view-course");
						return false;
					}

					/* can select any number of subs plans aslo when any number of clone are made. */
					var flag = 0;
				}
				<?php echo $this->form->getField('description')->save() ?>;
				jQuery(".btn-small").attr('disabled', true);

				Joomla.submitform(task, document.getElementById('course-form'));
			}
		}
	}

	jQuery(document).keypress(function (e) {
		if(e.which == 13 && e.target.nodeName != "TEXTAREA") return false;
	});
</script>

<div class="<?php echo COM_TJLMS_WRAPPER_DIV ?>">
	<form action="<?php echo Route::_('index.php?option=com_tjlms&view=course&layout=edit&id=' . (int) $this->item->id); ?>" method="post" enctype="multipart/form-data" name="adminForm" id="course-form" class="form-validate">
		<input type="hidden" name="task" value="" />
		<?php echo HTMLHelper::_('form.token'); ?>
		<div class="form-horizontal">
			<div class="row-fluid">
				<div class="span12">
				<!-- Add tabs in case of only paid,extra or permission data  -->
				<?php if ($this->form_extra || $allow_paid_courses == 1 || $this->canDo->get('core.create')) : ?>
						<?php echo HTMLHelper::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>

						<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'general', Text::_('COM_TJLMS_TITLE_COURSE', true)); ?>
				<?php endif; ?>
				<!-- End add tabs in case of only paid,extra or permission data  -->

				<!-- General tabs starts here  -->

				<div class="control-group" style="display:none">
					<div class="control-label"><?php echo $this->form->getLabel('id'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('id'); ?></div>
				</div>

				<?php echo $this->form->renderField('created_by'); ?>

				<?php echo $this->form->renderField('title'); ?>

				<?php echo $this->form->renderField('alias'); ?>

				<?php echo $this->form->renderField('catid'); ?>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('short_desc'); ?></div>
					<div class="controls">
						<?php echo $this->form->getInput('short_desc'); ?>
						<div class="sa_charlimit help-inline">
							<span id ="max_body1" ><?php echo $this->characters_allowed; ?></span>
							<span id="sBann1"><?php echo Text::_('COM_TJLMS_LEFT_CHAR');?></span>
						</div>
					</div>
				</div>
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('description'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('description'); ?></div>
				</div>
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('image'); ?></div>
					<div class="controls ">
						<?php echo $this->form->getInput('image'); ?>
						<span class="help-block">
							<?php echo Text::_('COM_TJLMS_SUPPORTED_MEDIA_FILES_COURSE'); ?>
						</span>
					</div>
				</div>

				<input type="hidden" name="jform[image]" id="jform_image_hidden" value="<?php echo $this->item->image ?>" />

				<?php if (!empty($this->item->image)) : ?>
					<div class="control-group">
						<div class="controls "><img src="<?php echo $this->courseImage ?>"></div>
					</div>
				<?php endif; ?>

				<?php echo $this->form->renderField('state'); ?>

				<?php echo $this->form->renderField('featured'); ?>

				<?php echo $this->form->renderField('start_date'); ?>

				<?php echo $this->form->renderField('access'); ?>

				<?php echo $this->form->renderField('certificate_term'); ?>

				<?php echo $this->form->renderField('certificate_id'); ?>

				<?php echo $this->form->renderField('expiry'); ?>

				<?php if ($this->tjlmsparams->get('social_integration', '', 'STRING') == 'easysocial'): ?>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('esbadges'); ?>
						</div>
						<div class="controls"><?php echo $this->form->getInput('esbadges'); ?>
						</div>
					</div>
				<?php endif; ?>

					<?php if ($this->enable_tags == 1): ?>
						<div class="control-group">
							<div class="control-label"><?php echo $this->form->getLabel('tags'); ?>
							</div>
							<div class="controls"><?php echo $this->form->getInput('tags'); ?>
							</div>
						</div>
					<?php endif; ?>
				<?php echo $this->form->getInput('group_id'); ?>
			<!--GENERAL TAB ENDS-->
				<?php if ($this->form_extra || $allow_paid_courses == 1 || $this->canDo->get('core.create')) : ?>
					<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
				<?php endif; ?>
				<!-- Details tabs ends here  -->

				<!--PRICING TAB START-->
				<?php if ($allow_paid_courses == 1): ?>
						<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'pricing', Text::_('COM_TJLMS_TITLE_TAB_PRICING', true)); ?>
							<!-- <fieldset class="adminform"> -->
							<?php echo $this->form->renderField('type'); ?>

							<?php echo $this->form->renderField('subsplans'); ?>

							<!-- </fieldset> -->
							<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
						<!--PRICING TAB ENDS-->

				<?php endif; ?>

				<?php /*@TODO : add commened code for tjField integration*/ ?>
				<!-- Other Details tab -->
				<?php

					/*if ($this->canDo->get('core.admin')) : ?>
					<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'otherdetails', Text::_('COM_TJLMS_OTHER_DETAILS', true)); ?>

					<?php
					if (!empty($this->item->id))
					{
						echo $this->loadTemplate('extrafields');
					}
					else
					{
					?>
						<div class="alert alert-info">
							<?php echo Text::_('COM_TJLMS_SAVE_OTHER_DETAILS_MSG');?>
						</div>
					<?php
					}
					?>
					<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
				<?php endif; */?>
				<!-- Other Details tab Ends-->


				<?php
				// Loading joomla's params layout to show the fields and field group added for the course.
				echo LayoutHelper::render('joomla.edit.params', $this); ?>

				<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'options', Text::_('COM_TJLMS_FIELDSET_OPTIONS', true)); ?>
						<?php echo $this->form->renderField('metadesc'); ?>
						<?php echo $this->form->renderField('metakey'); ?>
							<?php foreach ($this->form->getGroup('params') as $field) : ?>
								<?php echo $field->renderField(); ?>
							<?php endforeach; ?>
				<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

				<!-- Permission tab -->
				<?php if ($this->canDo->get('core.admin')) : ?>
					<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'permissions', Text::_('COM_TJLMS_FIELDSET_RULES', true)); ?>

					<?php echo $this->form->getInput('rules'); ?>
					<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
				<?php endif; ?>
				<!-- Permission tab End-->

				<!-- Add tabs in case of only paid,extra or permission data  -->
				<?php if ($this->form_extra || $allow_paid_courses == 1 || $this->canDo->get('core.admin')) : ?>

					<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>
				<?php endif; ?>
				<!-- Add tabs in case of only paid,extra or permission data  -->
			</div>
			</div>
		</div>
	</form>
</div> <!--techjoomla-bootstrap-->
