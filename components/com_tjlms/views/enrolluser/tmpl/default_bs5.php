<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2017. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormHelper;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('behavior.formvalidator');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$input  = Factory::getApplication()->input;
?>

<div id="tjlms-assign" class="tjlms-wrapper tjBs3">
<form  method="post" name="adminForm" id="adminForm" class="form-horizontal">
	<div class="modal-content tjlms-filters">
		<div class="modal-header ">
		<div class="row ">
			<!-- <button type="button" class="close" onclick="closeAssignRecommendPopups();">&times;</button> -->
		<?php if ($this->type == 'reco') { ?>
				<h3 class="modal-title tjmodal-recommend-title center">
					<?php echo Text::sprintf('TJLMS_RECOMMEND_MODEL_TITLE', $this->courseInfo->title);?>
				</h3>
		<?php	 }else{ ?>
				<h3 class="modal-title tjmodal-assign-title center pt-10 pb-10 pr-0 ">
					<?php echo Text::sprintf('COM_TJLMS_FORM_TITLE_ASSIGN_CONTENT', $this->courseInfo->title);?>
				</h3>
				<h3 class="modal-title tjmodal-enroll-title center pt-10 pb-10 pr-0 ">
					<?php echo Text::sprintf('COM_TJLMS_FORM_TITLE_ENROLL_CONTENT', $this->courseInfo->title);?>
				</h3>
				<div class="container-fluid mt-15 mb-10">
					<div class="assign col-md-6 col-sm-6">
						<label class="">
							<input id="select-option" type="checkbox" value="" onchange="showAssign(this); ">
								<?php echo Text::_('COM_TJLMS_ASSIGN_USER_CHECKBOX'); ?>
						</label>
					</div>
					<div class="col-md-6 col-sm-6">
						<label class="">
							<input id="notify_user_enroll" type="checkbox" name="notify_user" value="1" hecked="">
							<span>Notify User</span>
						</label>
					</div>
				</div>
				<div class="row show-assignment-fields ">
					<div class=" col-md-6">
						<?php $senderMessage = ($this->state->get('filter.sender_message')) ? $this->state->get('filter.sender_message') : '';?>

						<textarea rows="3" id="sender_msg" class="sender_msg full-width-height mb-10" name="sender_message" placeholder='<?php echo Text::_("COM_TJLMS_SAY_SOMETHING");?>' ><?php echo $senderMessage;?></textarea>
					</div>
					<div class=" col-md-6 date-fields">
						<?php

						$startDate = ($this->state->get('filter.start_date')) ? $this->state->get('filter.start_date') : Text::_("COM_TJLMS_START_DATE");
						$calendar = HTMLHelper::_("calendar", $startDate , 'start_date', 'start_date', '%Y-%m-%d', array('placeholder' => Text::_("COM_TJLMS_START_DATE") , 'class' => 'required input-medium'));
						echo str_replace('icon-calendar', 'glyphicon glyphicon-calendar', $calendar);

						$dueDate = ($this->state->get('filter.due_date')) ? $this->state->get('filter.due_date') : Text::_("COM_TJLMS_DUE_DATE");
						$calendar = HTMLHelper::_('calendar', $dueDate ,'due_date','due_date', '%Y-%m-%d', array('placeholder' => Text::_("COM_TJLMS_DUE_DATE"), 'class' => 'required input-medium'));
						echo str_replace('icon-calendar', 'glyphicon glyphicon-calendar', $calendar);
						?>
					</div>
				</div>
				<div class="controls ">
				<?php
				FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models/fields/');
				$Courses = FormHelper::loadFieldType('courses', false);
				$this->courseoptions=$Courses->getOptionsExternally();
				echo HTMLHelper::_('select.genericlist', $this->courseoptions, 'selectedcourse[]', 'class="btn input-medium" multiple="multiple" size="10" name="groupfilter"', "value", "text", $this->course_id);
		?>	</div>
		<?php } ?>
		</div>
		</div>
		<div class="modal-body enroll-users" id="enrollusers">
			<div class="techjoomla-bootstrap tjlms_enrolUsers">
					<?php if ($this->type != 'reco'): ?>
						<?php echo HTMLHelper::_('bootstrap.startTabSet', 'myTab', array('active' => 'users')); ?>
							<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'users', Text::_('COM_TJLMS_TITLE_USERS_ASSIGNMENT', true)); ?>
								<?php echo $this->loadTemplate('users'); ?>
							<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

							<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'groups', Text::_('COM_TJLMS_TITLE_GROUPS_ASSIGNMENT', true)); ?>
								<?php echo $this->loadTemplate('groups'); ?>
							<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

						<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>
					<?php else: ?>
						<?php echo $this->loadTemplate('users'); ?>
					<?php endif; ?>

					<input type="hidden" name="task" id="task" value="" />
					<input type="hidden" name="selectedcourse" id="selectedcourse" value="<?php echo (int) $this->course_id; ?>"/>
					<input type="hidden" name="boxchecked" value="0"/>
					<input type="hidden" name="type" id="type" value="<?php echo $this->type; ?>"/>
					<input type="hidden" name="option" value="com_tjlms" />
					<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
					<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
					<input type="hidden" name="sender_msg" id="sender_msg" value="" />
					<?php echo HTMLHelper::_('form.token'); ?>
			</div>
		</div>
		<div class="modal-footer">
			<div class="assign-footer tjlms-enroll-users">
				<?php if ($this->type != 'reco'): ?>
					<label class="assign-groups-btn pull-left alert alert-info" for="update_existing_users" id="update_existing_users">
						<input class="update-existing-users" type="checkbox" name="update_existing_users" value="" id="update_existing_users">
						<?php echo Text::_("COM_TJLMS_GROUP_ASSIGNMENT_EXISTING_UPDATE");?>
					</label>
				<?php endif;?>
				<a role="button" class="btn" aria-hidden="true" onclick="closeAssignRecommendPopups(); return false;">
					<?php echo Text::_('JLIB_HTML_BEHAVIOR_CLOSE');?>
				</a>
				<?php if ($this->type == 'reco'): ?>
				<a class="btn btn-primary enroll-btn" name="enroll" onclick="assignUser('enrollAssignWrapper','reco');return false;" value=""><?php echo Text::_('COM_TJLMS_RECOMMEND_LABEL'); ?></a>
				<?php else: ?>
				<a class="btn btn-primary enroll-btn" name="enroll" onclick="assignUser('enrollAssignWrapper','enroll');return false;" value=""><?php echo Text::_('TJLMS_COURSE_ENROL'); ?></a>
				<a class="btn btn-primary assign-btn " name="assign" onclick="assignUser('enrollAssignWrapper', 'assign');return false;" value=""><?php echo Text::_("COM_TJLMS_ASSIGN_LABEL");?></a>
				<a class="btn btn-primary assign-groups-btn " name="assign" id="assign" onclick="assignUser('enrollAssignWrapper', 'assignGroup');return false;" value=""><?php echo Text::_("COM_TJLMS_ASSIGN_LABEL");?></a>
				<a class="btn btn-primary enroll-groups-btn " name="assign" id="assign" onclick="assignUser('enrollAssignWrapper', 'enrollGroup');return false;" value=""><?php echo Text::_("TJLMS_COURSE_ENROL");?></a>
				<?php endif;?>
			</div>
		</div>
	</div>
</form>
</div>
<script>
jQuery(document).ready(function(){
	jQuery(".tjmodal-assign-title, .show-assignment-fields, #tjlms-assign .controls, .assign-groups-btn, .assign-btn, .enroll-groups-btn").hide();
	jQuery(".enroll-btn, .tjmodal-enroll-title").show();
	adjustModalHeight();
});
jQuery(window).resize(function(event) {
	adjustModalHeight();
});
</script>
