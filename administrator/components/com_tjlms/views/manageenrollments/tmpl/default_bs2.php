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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Component\ComponentHelper;

HTMLHelper::addIncludePath(JPATH_COMPONENT.'/helpers/html');

// Import helper for declaring language constant
JLoader::import('TjlmsHelper', JPATH_ROOT . 'administrator/components/com_tjlms/helpers/tjlms.php');
TjlmsHelper::getLanguageConstant();

HTMLHelper::script('administrator/components/com_tjlms/assets/js/tjlmsvalidator.js');

// ADDED to change date format
jimport('techjoomla.common');
$this->techjoomlacommon = new TechjoomlaCommon;
$lmsparams   = ComponentHelper::getParams('com_tjlms');
$date_format_show = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

if (JVERSION >= '3.0')
{
	HTMLHelper::_('bootstrap.tooltip');
	HTMLHelper::_('formbehavior.chosen', 'select');
	HTMLHelper::_('behavior.multiselect');
}

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.modal');

echo HTMLHelperBootstrap::renderModal('import', $this->user_csv_params);
echo $this->addToolbar();

// Import CSS
$document = Factory::getDocument();
$document->addStyleSheet(JUri::root() . 'components/com_tjlms/assets/css/tjlms.css');

$user      = Factory::getUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canOrder  = $user->authorise('core.edit.state', 'com_tjlms');
$saveOrder = $listOrder == 'b.ordering';
$input     = Factory::getApplication()->input;

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_tjlms&task=manageenrollments.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'usersList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
$courseId = $input->get('course_id', '', 'INT');
$courseParam = '';
?>
<script type="text/javascript">
	jQuery(document).ready(function(){

		jQuery('#tjlms_import-csv').on('hidden', function () {
			window.location.reload(true);
		});

		if (jQuery("#filter_coursefilter").val() || jQuery("#filter_state").val())
		{
			jQuery('.js-stools-btn-filter').addClass('btn-primary');
			jQuery('.js-stools-container-filters').addClass('show');
		}

		jQuery('a.studentsReport').addClass('rel');
	});

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

var root_url	=	"<?php echo JURI::base();?>";
jQuery(document).ready(function() {
	var width = jQuery(window).width();
	var height = jQuery(window).height();
	jQuery('a.studentsReport').attr('rel','{handler: "iframe", size: {x: '+(width-(width*0.20))+', y: '+(height-(height*0.20))+'},classWindow: "tjlms-modal"}');
});

/* Code to change Due date of enrollment*/
	function changeDateCourse(element_id, notify, start_date, due_date, todo_id, recommend_friends)
	{
		start_date = jQuery('#' + start_date).val();
		var or_start_date = start_date;
		due_date   = jQuery('#' + due_date).val();
		var or_due_date = due_date;
		var notify_user = 0;
		var response  = valid_dates_manage(start_date,due_date);

			if (response)
			{
				if (jQuery('#' + notify).is(":checked"))
				{
					var notify_user = 1 ;
				}

				jQuery("#start_date").val(or_start_date);
				jQuery("#due_date").val(or_due_date);
				document.getElementById('notify_user_manage').value = notify_user;
				document.getElementById('element_id').value = element_id;
				document.getElementById('todo_id').value = todo_id;
				document.getElementById('recommend_friends').value = recommend_friends;
				Joomla.submitform('manageenrollments.updateAssignmentDate()', document.getElementById('adminForm'));
			}

	}
function submitFromCalendar(elem)
{
	return validateSubmit(true,elem);
}
var origSdate = '<?php echo $this->state->get('filter.enroll_starts')?>';
var origEdate = '<?php echo $this->state->get('filter.enroll_ends')?>';
function submitFromCalendar(elem)
{
	return validateSubmit(true,elem);
}
function validateSubmit(fromCalendar,elem)
{
	var valid = checkValidCalVal();
	if(fromCalendar && (valid || ((origSdate && origSdate != document.getElementById("filter_enroll_starts").value) || (origEdate && origEdate != document.getElementById("filter_enroll_ends").value))))
	{
		valid = checkValidStartEnd(elem);
		if(valid)
		{
			jQuery("#adminForm").submit();
		}
	}
	else if(!valid)
	{
		dispEnqueueMessage(Joomla.JText._('COM_TJLMS_MANAGEENROLLMENTS_INVALID_DATE_FORMAT'),'invalid_dates');
	}
	return true;
}
function checkValidCalVal()
{
	var valid = true;
	var validStart = document.formvalidator.validate(document.getElementById("filter_enroll_starts"));

	if(!validStart)
	{
		document.getElementById("filter_enroll_starts").value='';
		valid = false;
	}
	else if(startDate = document.getElementById("filter_enroll_starts").value)
	{
		sdTimeStamp = getTimeStampFromDate(startDate);
	}

	var validEnd = document.formvalidator.validate(document.getElementById("filter_enroll_ends"));

	if(!validEnd)
	{
		document.getElementById("filter_enroll_ends").value='';
		valid = false;
	}

	return valid;
}
function checkValidStartEnd(changedElem)
{
	var valid = true;
	var startDate, sdTimeStamp, endDate, edTimeStamp;
	var validStart = document.formvalidator.validate(document.getElementById("filter_enroll_starts"));

	if(startDate = document.getElementById("filter_enroll_starts").value)
	{
		sdTimeStamp = getTimeStampFromDate(startDate);
	}

	if(endDate = document.getElementById("filter_enroll_ends").value)
	{
		edTimeStamp = getTimeStampFromDate(endDate);
	}

	if(changedElem && sdTimeStamp && edTimeStamp)
	{
		if(sdTimeStamp > edTimeStamp)
		{
			document.getElementById("filter_enroll_starts").value=origSdate;
			document.getElementById("filter_enroll_ends").value=origEdate;
			dispEnqueueMessage(Joomla.JText._('COM_TJLMS_MANAGEENROLLMENTS_DATE_RANGE_VALIDATION'),'starts_ends');
			valid = false;
		}
	}

	return valid;
}

function changeCourseStatus(courseTrackId, currentValue, enrolledUserId, courseId, previousValue)
{
	if (confirm(Joomla.JText._('COM_TJLMS_CHANGE_COURSE_STATUS')))
	{
		document.getElementById('courseTrackId').value  = courseTrackId;
		document.getElementById('courseStatus').value   = currentValue.value;
		document.getElementById('enrolledUserId').value = enrolledUserId;
		document.getElementById('cId').value            = courseId;

		Joomla.submitform('manageenrollments.changeCourseStatus', document.getElementById('adminForm'));
	}
	else
	{
		jQuery('#' + currentValue.id).val(previousValue).trigger("liszt:updated")
	}
}
</script>


<?php
$enrolledclass = "";

if (!empty($courseId))
{
	$enrolledclass = "manageenrollpopup";
}
?>

<?php

//Joomla Component Creator code to allow adding non select list filters
if (!empty($this->extra_sidebar))
{
	$this->sidebar .= $this->extra_sidebar;
}

if ($courseId)
{
	$courseParam = '&tmpl=component&course_id='.$courseId;
}
?>


<div class="<?php echo COM_TJLMS_WRAPPER_DIV ?> <?php echo $enrolledclass ?>">
<?php
	if ($courseId && $this->courseInfo)
	{
	?>
	<div class="modal-header">
		<button type="button" class="close" onclick="closePopup();"; data-dismiss="modal" aria-hidden="true">×</button>

		<h3><?php echo JText::sprintf('COM_TJLMS_MANAGE_ENROLLED_USERS_FOR_COURSE',$this->courseInfo->title); ?>
	</div>
<?php } ?>
<form action="<?php echo Route::_('index.php?option=com_tjlms&view=manageenrollments'.$courseParam ); ?>" method="post" name="adminForm" id="adminForm">

	<?php
			ob_start();
			include JPATH_BASE . '/components/com_tjlms/layouts/header.sidebar.php';
			$layoutOutput = ob_get_contents();
			ob_end_clean();
			echo $layoutOutput;

			echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
	?> <!--// HTMLHelpersidebar for menu ends-->


	<?php if(JVERSION < '3.0'):	?>
		<div id="filter-bar" class="btn-toolbar">
			<div class="filter-search btn-group pull-left">
				<input type="text" name="filter_search" id="filter_search" placeholder="<?php echo JText::_('JSEARCH_FILTER_BY_USER_NAME'); ?>" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo JText::_('JSEARCH_FILTER'); ?>" />
			</div>
			<div class="btn-group pull-left">
				<button class="btn hasTooltip" type="submit" title="<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i></button>
				<button class="btn hasTooltip" type="button" title="<?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?>" onclick="document.id('filter_search').value='';this.form.submit();"><i class="icon-remove"></i></button>
			</div>
			<div class="pull-right">
				<?php
				if (!$courseId)
				{
					//echo HTMLHelper::_('select.genericlist', $this->coursefilter, "coursefilter", 'class="" size="1" onchange="document.adminForm.submit();" name="coursefilter"', "value", "text", $this->state->get('filter.coursefilter'));
				}
					echo HTMLHelper::_('select.genericlist', $this->sstatus, "filter_published", 'class="" size="1" onchange="document.adminForm.submit();" name="filter_published"', "value", "text", $this->state->get('filter.state'));
				?>
			</div>
		</div>

	<?php endif; ?>


	<div class="clearfix"> </div>

	<?php if (empty($this->items)) : ?>
				<div class="alert alert-no-items">
					<?php echo JText::_('COM_TJLMS_NO_ENROLLMENT_AVAILABLE'); ?>
				</div>
			</div><!--j-main-container ends-->
		</div> <!--CLose wrapper div as we return from here-->
		<?php return; ?>
	<?php endif;  ?>

	<div class="tjlms-tbl">
			<table class="table table-striped left_table" id="usersList">
				<thead>
					<tr>
						<?php
						$displayToggel = '';

						if ($courseId)
						{
							$displayToggel = 'display:none';
						}?>

						<th width="1%" class="hidden-phone" style="<?php echo $displayToggel; ?>">
							<?php echo HTMLHelper::_('grid.checkall'); ?>
						</th>

						<?php if (!$courseId && isset($this->items[0]->state)):	?>
						<th width="1%" class="nowrap center">
							<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
						</th>
						<?php endif; ?>

						<?php
						if (!$courseId)
						{
							if (isset($this->items[0]->id)): ?>
								<th width="1%" class="nowrap center hidden-phone">
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_TJLMS_MANAGEENROLLMENTS_ID', 'b.id', $listDirn, $listOrder); ?>
								</th>
							<?php endif;
						}
						else
						{
							?>
							<th></th><!--Blank th added to view report column-->
							<?php
						}
						?>

						<th class='left'>
							<?php echo HTMLHelper::_('searchtools.sort',  'COM_TJLMS_ENROLMENT_USER_NAME', 'uc.name', $listDirn, $listOrder); ?>
						</th>

						<?php if (!$courseId) {	?>
						<th class='left'>
						<?php echo HTMLHelper::_('searchtools.sort',  'COM_TJLMS_MANAGEENROLLMENTS_COURSE_ID', 'co.title', $listDirn, $listOrder); ?>
						</th>
						<?php } ?>

						<th class='left'>
							<?php echo HTMLHelper::_('searchtools.sort',  'COM_TJLMS_ENROLMENT_USER_USERNAME', 'uc.username', $listDirn, $listOrder); ?>
						</th>

						<th class='left'>
							<?php echo HTMLHelper::_('searchtools.sort',  'COM_TJLMS_ENROLMENT_START_DATE_TITLE', 'a.start_date', $listDirn, $listOrder); ?>
						</th>

						<th class='left'>
							<?php echo HTMLHelper::_('searchtools.sort',  'COM_TJLMS_ENROLMENT_DUE_DATE_TITLE', 'a.due_date', $listDirn, $listOrder); ?>
						</th>
						<th class='left'>
							<?php  echo HTMLHelper::tooltip(JText::_('COM_TJLMS_NOTIFY_ASSIGN_USER'), '','', JText::_('COM_TJLMS_NOTIFY_ASSIGN_USER'));?>
						</th>

						<th class='left'>
						</th>

						<th class='left'>
							<?php	echo HTMLHelper::tooltip(JText::_('COM_TJLMS_ENROLMENT_GROUP_TITLE'), '','', JText::_('COM_TJLMS_ENROLMENT_GROUP_TITLE')); ?>
						</th>
						<!-- <th class='left'>
							<?php //echo Text::_('COM_TJLMS_COURSE_COMPLETION_STATUS_TITLE'); ?>
						</th> -->

						<th class='left'>
							<?php  echo HTMLHelper::tooltip(JText::_('COM_TJLMS_SUBSCRIPTION_END_DATE_DESC'), '','', JText::_('COM_TJLMS_SUBSCRIPTION_END_DATE_TITLE'));?>
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
						<td colspan="<?php echo $colspan ?>">
							<?php echo $this->pagination->getListFooter(); ?>
						</td>
					</tr>
				</tfoot>
				<tbody>

				<?php foreach ($this->items as $i => $item) :
					$ordering   = ($listOrder == 'b.ordering');
					$canCreate	= $user->authorise('core.create',		'com_tjlms');
					$canEdit	= $user->authorise('core.edit',			'com_tjlms');
					$canCheckin	= $user->authorise('core.manage',		'com_tjlms');
					$canChange	= $user->authorise('core.edit.state',	'com_tjlms');

					$courseStatus = array();

					if ($item->courseStatus != 'C')
					{
						$courseStatus[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_COURSE_COMPLETION_STATUS'));
						$courseStatus[] = HTMLHelper::_('select.option', 'I', Text::_('COM_TJLMS_LESSONSTATUS_INCOMPLETE'));
					}

					$courseStatus[] = HTMLHelper::_('select.option', 'C', Text::_('COM_TJLMS_FILTER_STATUS_COMPLETED'));
					?>
					<tr class="row<?php echo $i % 2; ?>">
						<td class="center hidden-phone" style="<?php echo $displayToggel; ?>">
							<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
						</td>

					<?php if (!$courseId):	?>
						<?php if (isset($this->items[0]->state)): ?>
							<td class="center">
								<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'manageenrollments.', $canChange, 'cb'); ?>
							</td>
						<?php endif; ?>
					<?php endif; ?>
					<?php
					if (!$courseId)
					{
						if (isset($this->items[0]->id)): ?>
							<td class="center hidden-phone">
								<a href="<?php echo Route::_('index.php?option=com_tjlms&view=manageenrollment&layout=edit&id=' . $item->id, false); ?>">
									<?php echo (int) $item->id; ?>
								</a>
							</td>
						<?php endif;
					}
					else
					{
						$studenCoursereport = 'index.php?option=com_tjlms&view=lessonreport&filter[coursefilter]=' . $item->course_id . '&filter[userfilter]=' . $item->user_id . '&usedAsPopupReport=1&tmpl=component';
						?>
						<td>

						<a class="modal studentsReport" href="<?php echo $studenCoursereport; ?>" ><?php echo JText::_('COM_TJLMS_STUDENT_COURSE_REPORT_DASHBOARD'); ?></a>


						</td>
						</td>
						<?php
					}
					?>
					<td>
						<?php echo $this->escape($item->name); ?>
					</td>
					<?php if (!$courseId)
					{	?>
					<td>
						<?php echo $item->title; ?>
					</td>
					<?php } ?>
					<td>
						<?php echo $item->username; ?>
					</td>
					<td>
					<?php
						$start_enroll_date = "";

						if ($item->start_date)
						{
							$start_enroll_date = HTMLHelper::date($item->start_date, 'Y-m-d', true);

							echo HTMLHelper::calendar($start_enroll_date, 'mycalendar', "start_{$i}", '%Y-%m-%d', array('size'=>'8', 'maxlength'=>'10', 'class'=>'input-small',));
						}
						else
						{
							echo HTMLHelper::calendar($start_enroll_date, 'mycalendar', "start_{$i}", '%Y-%m-%d', array('size'=>'8', 'maxlength'=>'10', 'class'=>'input-small',));
						}
							?>
					</td>
					<td>
						<?php
						$due_date = "";

						if ($item->due_date)
						{
							$due_date = HTMLHelper::date($item->due_date, 'Y-m-d', true);

							echo HTMLHelper::calendar($due_date,'mycalendar', "due_{$i}", '%Y-%m-%d',array('size'=>'8','maxlength'=>'10','class'=>'input-small',));
						}
						else
						{
							echo HTMLHelper::calendar($due_date,'mycalendar', "due_{$i}", '%Y-%m-%d',array('size'=>'8','maxlength'=>'10','class'=>'input-small',));
						}
						?>
					</td>
					<td>
						<label>
						<input id="notify_user_<?php echo $i ?>" type="checkbox" name='notify_user_per' checked="checked">
					</label>
					</td>
					<td><?php
							if (empty($item->todo_id))
							{
								$item->todo_id = 0;
							}
						?>
						<input type="button" id="assign_<?php echo $i ?>" class="btn btn-success" name="assign" onclick="changeDateCourse(<?php echo $item->course_id ?> , 'notify_user_<?php echo $i ?>', 'start_<?php echo $i ?>', 'due_<?php echo $i ?>', <?php echo $item->todo_id ?>  ,  <?php echo $item->user_id  ?>)"  value="<?php echo JText::_('COM_TJLMS_ASSIGN_USER_BUTTON'); ?>" />
					</td>

					<td>
						<?php if (substr_count($item->groups, "<br />") > 2) : ?>
							<span class="hasTooltip" title="<?php echo HTMLHelper::_('tooltipText', JText::_('COM_TJLMS_ENROLMENT_GROUP_TITLE'), nl2br($item->groups), 0); ?>"><?php echo JText::_('COM_TJLMS_ENROLMENT_MULTI_GROUP_TITLE'); ?></span>
						<?php else : ?>
							<?php echo nl2br($item->groups); ?>
						<?php endif; ?>
					</td>

					<!-- <td>
						<?php
							echo HTMLHelper::_('select.genericlist', $courseStatus, "courseStatus" .
							$i, 'class="pad_status input-small cStatus" id="changeCourseStatus" onChange="changeCourseStatus(' . $item->courseTrackId . ', this, ' . $item->user_id .',' . $item->course_id . ', \'' . $item->courseStatus . '\')"', "value", "text", $item->courseStatus); ?>
					</td> -->

					<td>
						<?php
						if ($item->end_time && $item->type == 1)
						{
							echo $item->end_time;
						}
						?>
					</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<input type="hidden" name="task" value="" />
		<input type="hidden" id='element_id' name="element_id" value="" />
		<input type="hidden" id="start_date" name="start_date" value="" />
		<input type="hidden" id="due_date" name="due_date" value="" />
		<input type="hidden" id='todo_id' name="todo_id" value="" />
		<input type="hidden" id='notify_user_manage' name="notify_user" value="" />
		<input type="hidden" id='recommend_friends' name="recommend_friends" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" id='courseTrackId' name="courseTrackId" value="" />
		<input type="hidden" id='courseStatus' name="courseStatus" value="" />
		<input type="hidden" id='enrolledUserId' name="enrolledUserId" value="" />
		<input type="hidden" id='cId' name="cId" value="" />
		<input type="hidden" name="rUrl" value="<?php echo base64_encode(JURI::getInstance()); ?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div><!--j-main-container ENDS-->
	</div><!--row-fluid ENDS-->

	<div class="batch_modal">
		<?php if ($canChange)				 : ?>
			<?php echo HTMLHelper::_(
				'bootstrap.renderModal',
				'collapseModal',
				array(
					'title' => JText::_('COM_TJLMS_USERS_ENROLLMENT_OPTIONS'),
					'footer' => $this->loadTemplate('batch_assign_footer')
				),
				$this->loadTemplate('batch_assign')
			); ?>
		<?php endif;?>
	</div>
</form>

</div><!--wrapper ends-->



