<?php
/**
 * @version     1.0.0
 * @package     com_tjlms
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      TechJoomla <extensions@techjoomla.com> - http://www.techjoomla.com
 */

// no direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.keepalive');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');


// Import helper for declaring language constant
JLoader::import('TjlmsHelper', JPATH_ROOT . 'administrator/components/com_tjlms/helpers/tjlms.php');
TjlmsHelper::getLanguageConstant();
JHtml::script('administrator/components/com_tjlms/assets/js/tjlmsvalidator.js');

$input = JFactory::getApplication()->input;
$user	= JFactory::getUser();
$userId	= $user->get('id');
$listOrder	= $this->state->get('list.ordering');
$listDirn	= $this->state->get('list.direction');
$canOrder	= $user->authorise('core.edit.state', 'com_tjlms');
$saveOrder	= $listOrder == 'a.ordering';
$selectedcourse = $this->state->get('filter.selectedcourse');
$course_al = $input->get('course_al','','INT');
$part = '';

$str = implode(',',$selectedcourse);
if ($course_al)
{
	$part = '&selectedcourse[]='.$str.'&course_al=' . $course_al;
}

$link = 'index.php?option=com_tjlms&view=enrolment&tmpl=component' . $part;
$rUrl = base64_encode($link);

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_tjlms&task=enrolment.saveOrderAjax&tmpl=component';
	JHtml::_('sortablelist.sortable', 'usersList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

$post = $input->post;

$course_selection = '';
if($course_al)
{
	$course_selection = 'course_selection';
}

?>


<div class="modal-header">
	<button type="button" class="close" onclick="window.parent.location.reload()"; data-dismiss="modal" aria-hidden="true">×</button>

	<h3><?php if(isset($this->courseInfo->title))
		{
			echo JText::sprintf('COM_TJLMS_TOTAL_ENROLLED_USERS_FOR_COURSE',$this->courseInfo->title);
		}else
		{
			echo JText::_('COM_TJLMS_MANAGEENROLLMENTS_NEW');
		}?>
</h3>
</div>

<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?>">
	<form method="post" name="adminForm" id="adminForm">
		<div class="control-group course_id_row <?php echo $course_selection; ?>">
			<div class="control-label">
				<label id="jform_title-lbl" for="jform_title" class="hasTooltip required" title="" data-original-title="<strong>Title</strong><br />Course title">
					<?php echo JText::_('COM_TJLMS_SELECT_COURSE'); ?><span class="star">&nbsp;*</span>
				</label>
			</div>
			<div class="controls">
				<?php
				JFormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models/fields/');
				$Courses = JFormHelper::loadFieldType('courses', false);
				$this->courseoptions=$Courses->getOptionsExternally();
				echo JHtml::_('select.genericlist', $this->courseoptions, 'selectedcourse[]', 'class="btn input-medium" multiple="multiple" size="10" name="groupfilter"', "value", "text",$selectedcourse);
		?>	</div>
		</div>

		<div class="form-actions">
			<div class="assign span2">
				<div class="checkbox">
					<label>
						<input id="select-option" type="checkbox" value="">
							<?php echo JText::_('COM_TJLMS_ASSIGN_USER_CHECKBOX'); ?>
					</label>
				</div>
			</div>
			<div id="enroll-user" class='span10'>
				<div class='row-fluid'>
					<div class="assign-date span7" >
						<?php
						echo JHtml::calendar(JHtml::date('now', 'D d M Y H:i', true),'start_date','start_date', '%Y-%m-%d' , array('placeholder'=>JText::_("COM_TJLMS_START_DATE") ,'class'=>'validate-datetime span9',));

						echo JHtml::calendar('','due_date','due_date', '%Y-%m-%d', array('placeholder'=>JText::_("COM_TJLMS_DUE_DATE") ,'class'=>'validate-datetime span9',));
						?>
					</div>
					<div class="span5 pull-right">
						<div class="row-fluid">
							<div class="span4">
								<label id="user1">
									<input id="notify_user_enroll" type="checkbox" name='notify_user_enroll' value="1" checked>
									<span><?php echo JText::_('COM_TJLMS_NOTIFY_ASSIGN_USER'); ?></span>
								</label>
							</div>
							<div class="span3 pull-right">
								<button class="btn inline btn-block label-btn btn-danger" type="button" onclick="closePopUp()" value="" /><?php echo JText::_('COM_TJLMS_CANCEL_BUTTON'); ?></button>
							</div>
							<button class="span4 btn btn-block btn-primary pull-right" type="button" name="enrol" id="enrol" onclick="Joomla.submitbutton('enrollAssignWrapper','enrol')" value="" /><?php echo JText::_('COM_TJLMS_ENROL_USER_BUTTON'); ?></button>

							<button class="btn  btn-success span4 pull-right" type="button" name="assign" id="assign" onclick="Joomla.submitbutton('enrollAssignWrapper', 'assign')"  value="" /><?php echo JText::_('COM_TJLMS_ASSIGN_USER_BUTTON'); ?></button>
						</div>
					</div>
				</div>
			</div><!--enroll-user-->
		</div><!--form-actions-->

		<?php
		if ($course_al)
		{
			echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));
		}
		else
		{
			$this->filterForm->removeField('groupfilter', 'filter');
			echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));
		}

		if (empty($this->items))
		{	?>
		<div class="alert alert-warning">
			<?php	echo JText::_('COM_TJLMS_NO_ENROLLED_USER_FOR_ACCESS_LEVEL');	?>
		</div>
		<?php
		}else{	?>

		<table class="table table-striped" id="usersList">
			<thead>
				<tr>
					<th width="1%" class="hidden-phone">
						<input type="checkbox" name="checkall-toggle" value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
					</th>

					<th class='left'>
						<?php echo JHtml::_('grid.sort',  'COM_TJLMS_ENROLMENT_USER_USERNAME', 'uc.username', $listDirn, $listOrder); ?>
					</th>
					<th class='left'>
						<?php echo JHtml::_('grid.sort',  'COM_TJLMS_ENROLMENT_USER_NAME', 'uc.name', $listDirn, $listOrder); ?>
					</th>
					<th class='left'>
						<?php echo JHtml::_('grid.sort',  'COM_TJLMS_ENROLMENT_GROUP_TITLE', 'title', $listDirn, $listOrder); ?>
					</th>
					<th class='left'>
					<?php echo JHtml::_('grid.sort',  'COM_TJLMS_ENROLMENT_USERID', 'uc.id', $listDirn, $listOrder); ?>
					</th>
				</tr>
			</thead>

			<tfoot>
				<?php
				if(isset($this->items[0])){
					$colspan = count(get_object_vars($this->items[0]));
				}
				else{
					$colspan = 10;
				}
			?>
				<tr>
					<td colspan="6">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
			<tbody>
			<?php

			foreach ($this->items as $i => $item) :
				$ordering   = ($listOrder == 'a.ordering');
                $canCreate	= $user->authorise('core.create', 'com_tjlms');
                $canEdit	= $user->authorise('core.edit', 'com_tjlms');
                $canCheckin	= $user->authorise('core.manage', 'com_tjlms');
                $canChange	= $user->authorise('core.edit.state', 'com_tjlms');
				?>
				<tr class="row<?php echo $i % 2; ?>">

					<td class="center hidden-phone">
						<?php echo JHtml::_('grid.id', $i, $item->id); ?>
					</td>

					<td>
						<?php echo $item->username; ?>
					</td>
					<td>
						<?php echo $item->name; ?>
					</td>
					<td>
						<?php if (substr_count($item->groups, "<br />") > 2) : ?>
							<span class="hasTooltip" title="<?php echo JHtml::_('tooltipText', JText::_('COM_TJLMS_ENROLMENT_GROUP_TITLE'), nl2br($item->groups), 0); ?>"><?php echo JText::_('COM_TJLMS_ENROLMENT_MULTI_GROUP_TITLE'); ?></span>
						<?php else : ?>
							<?php echo nl2br($item->groups); ?>
						<?php endif; ?>
						<!-- <?php echo $item->groups; ?> -->
					</td>
					<td>
						<?php echo $item->id; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		}	?>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<input type="hidden" id="rUrl" name="rUrl" value=<?php echo $rUrl; ?> />
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>

<script type="text/javascript">
  Joomla.orderTable = function() {
	table = document.getElementById("sortTable");
	direction = document.getElementById("directionTable");
	order = table.options[table.selectedIndex].value;
	if (order != '<?php echo $listOrder; ?>') {
		dirn = 'asc';
	} else {
		dirn = direction.options[direction.selectedIndex].value;
	}
	Joomla.tableOrdering(order, dirn, '');
}

Joomla.submitbutton = function(task, operation)
{
	if (operation=='enrol')
	{
			if (!jQuery('#selectedcourse').val())
			{
				alert("<?php echo  JText::_('COM_TJLMS_SELECT_COURSE_TO_ENROLL'); ?>")
				return false;
			}


		if (document.adminForm.boxchecked.value==0)
		{
			alert('<?php echo JText::_("COM_TJLMS_MESSAGE_SELECT_ITEMS");?>');
			return false;
		}
		else
		{
			Joomla.submitform('enrolment.'+task);
		}
	}
	else if(operation=='assign')
	{

			if (!jQuery('#selectedcourse').val())
			{
				alert("<?php echo  JText::_('COM_TJLMS_SELECT_COURSE_TO_ENROLL'); ?>")
				return false;
			}

		if (document.adminForm.boxchecked.value==0)
		{
			alert('<?php echo JText::_("COM_TJLMS_MESSAGE_SELECT_ITEMS");?>');
			return false;
		}
		else
		{
			var response  = valid_dates("start_date","due_date","assignUser");

			if (response)
			{
				Joomla.submitform('enrolment.'+task);
			}
		}
	}
	else
	{
		if(document.adminForm.boxchecked.value==0)
		{
			alert('<?php echo JText::_("COM_TJLMS_MESSAGE_SELECT_ITEMS");?>');
			return false;
		}

		Joomla.submitform(task);
	}
}

function closePopUp()
{
	window.parent.SqueezeBox.close();
}
</script>

