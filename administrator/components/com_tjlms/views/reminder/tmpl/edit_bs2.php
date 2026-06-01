<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Tjlms
 * @author     TechJoomla <contact@techjoomla.com>
 * @copyright  Copyright (C) 2014 - 2016. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.keepalive');

// Import CSS
$document = JFactory::getDocument();
$document->addStyleSheet(JUri::root() . 'media/com_tjlms/css/form.css');
?>
<script type="text/javascript">
	js = jQuery.noConflict();
	js(document).ready(function () {

		js("#jform_select_course").removeClass("invalid");

		js("#jform_select_course").change(function(event){
				var count = js(this).add(" :selected").length;
				if(count === 1)
				{
					js(this).add(" #jform_select_course_chzn").addClass("custominvalid");
					js("#jform_select_course-lbl").addClass("custominvalid");
				}
				else
				{
					js(this).add(" #jform_select_course_chzn").removeClass("custominvalid");
					js("#jform_select_course-lbl").removeClass("custominvalid");
				}
			});

	});


	function number()
	{
		var days = document.getElementById('jform_days').value;

		if (days <= 0)
		{
			alert("<?php echo JText::_('COM_TJLMS_FORM_REMINDER_DAYS_ZERO'); ?>");
			jQuery('#jform_days').val('1');

			return false;
		}

		return true;
	}

	Joomla.submitbutton = function (task) {
		if (task == 'reminder.cancel') {
			Joomla.submitform(task, document.getElementById('reminder-form'));
		}
		else {

			if (task != 'reminder.cancel' && document.formvalidator.isValid(document.id('reminder-form'))) {

				Joomla.submitform(task, document.getElementById('reminder-form'));
			}
			else {
				alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
			}
		}
	}
</script>

<form
	action="<?php echo JRoute::_('index.php?option=com_tjlms&layout=edit&id=' . (int) $this->item->id); ?>"
	method="post" enctype="multipart/form-data" name="adminForm" id="reminder-form" class="form-validate">

	<div  class="techjoomla-bootstrap">
	<table border="0" width="100%" cellspacing="10" class="adminlist">
		<tr>
			<td width="70%" align="left" valign="top">

	<div class="form-horizontal">
		<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>

		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'general', JText::_('COM_TJLMS_TITLE_REMINDER', true)); ?>
		<div class="row-fluid">
			<div class="span10 form-horizontal">
				<fieldset class="adminform">

									<input type="hidden" name="jform[id]" value="<?php echo $this->item->id; ?>" />
				<input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering; ?>" />
				<input type="hidden" name="jform[state]" value="<?php echo $this->item->state; ?>" />
				<input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
				<input type="hidden" name="jform[checked_out_time]" value="<?php echo $this->item->checked_out_time; ?>" />

				<?php if (empty($this->item->created_by))
				{?><input type="hidden" name="jform[created_by]" value="<?php echo JFactory::getUser()->id; ?>" />
				<?php }
				else{ ?>
					<input type="hidden" name="jform[created_by]" value="<?php echo $this->item->created_by; ?>" />
				<?php } ?>
				<?php if (empty($this->item->modified_by))
				{ ?>
					<input type="hidden" name="jform[modified_by]" value="<?php echo JFactory::getUser()->id; ?>" />
				<?php }
				else{?>
					<input type="hidden" name="jform[modified_by]" value="<?php echo $this->item->modified_by; ?>" />
					<?php } ?>			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('title'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('title'); ?></div>
			</div>
			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('days'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('days'); ?></div>
			</div>
			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('select_course'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('select_course'); ?></div>
			</div>

			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('subject'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('subject'); ?></div>
			</div>

			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('description'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('description'); ?></div>
			</div>

			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('email_template'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('email_template'); ?></div>
			</div>



<!--

					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('version_note'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('version_note'); ?></div>
					</div>

-->

				</fieldset>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>



		<?php echo JHtml::_('bootstrap.endTabSet'); ?>

		<input type="hidden" name="task" value=""/>
		<?php echo JHtml::_('form.token'); ?>

	</div>
	</td>
			<td width="30%" valign="top">
				<table>
<!--
					<tr>
						<td colspan="2"><div class="alert alert-info"><?php echo JText::_('EB_CSS_EDITOR_MSG') ?> <br/></div>
							<?php echo $this->form->getInput('css'); ?>
						</td>
					</tr>
-->
					<tr>
						<td colspan="2"><div class="alert alert-info"><?php echo JText::_('COM_TJLMS_EB_TAGS_DESC') ?> <br/></div>
										</tr>

					<tr>
						<td width="30%"><b>&nbsp;&nbsp;{courses.title} </b> </td>
						<td><?php echo JText::_('TAGS_COURSE_NAME'); ?></td>

					</tr>

					<tr>
						<td><b>&nbsp;&nbsp;{enrollment.username}</b> </td>
						<td><?php echo JText::_('TAGS_COURSE_ENROLLMENT_USERNAME'); ?></td>

					</tr>
					<tr>
						<td><b>&nbsp;&nbsp;{enrollment.name}</b> </td>
						<td><?php echo JText::_('TAGS_COURSE_ENROLLMENT_NAME'); ?></td>

					</tr>
					<tr>
						<td><b>&nbsp;&nbsp;{course_link}</b></td>
						<td><?php echo JText::_('TAGS_COURSE_LINK'); ?></td>

					</tr>

					<tr>
						<td><b>&nbsp;&nbsp;{courses.short_desc}</b></td>
						<td><?php echo JText::_('TAGS_COURSE_DESCRIPTION'); ?></td>

					</tr>
					<tr>
						<td><b>&nbsp;&nbsp;{courses.due_date} </b></td>
						<td><?php echo JText::_('TAGS_COURSE_DUE_DATE'); ?></td>

					</tr>


				</table>
			</td>
		</tr>
	</table>
</form>
