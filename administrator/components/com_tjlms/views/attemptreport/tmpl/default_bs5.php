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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('jquery.token');
HTMLHelper::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/helpers/html');

if (JVERSION >= '3.0')
{
	HTMLHelper::_('bootstrap.tooltip');
	HTMLHelper::_('behavior.multiselect');
}
else
{
	HTMLHelper::_('behavior.modal');
	HTMLHelper::_('behavior.formvalidator');
}

// Import CSS
$document = Factory::getDocument();

$user	= Factory::getUser();
$userId	= $user->get('id');
$listOrder	= $this->state->get('list.ordering');
$listDirn	= $this->state->get('list.direction');
$canOrder	= $user->authorise('core.edit.state', 'com_tjlms');
$saveOrder	= $listOrder == 'a.ordering';
$input = Factory::getApplication()->input;
$tmpl = '';

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_tjlms&task=manageenrollments.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'usersList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

?>
<script type="text/javascript">
	jQuery(document).ready(function(){

			jQuery('.input-append .active').removeClass('active');

			if (jQuery("#filter_coursefilter").val() || jQuery("#filter_state").val() || jQuery("#filter_userfilter").val() || jQuery("#filter_statusfilter").val())
		{
			jQuery('.js-stools-btn-filter').addClass('btn-primary');
			jQuery('.js-stools-container-filters').addClass('show');
		}
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

	function changeLessonStatus(attemptId, currentValue)
	{
		document.getElementById('attempt_id').value = attemptId;
		document.getElementById('lesson_status').value = currentValue;
		Joomla.submitform('attemptreport.changeLessonStatus', document.getElementById('adminForm'));
	}

	function resetElement(event, attemptId)
	{
		jQuery(".score-tr-"+attemptId+ " .user-score").show();
		jQuery(".score-tr-"+attemptId+ " .marks-edit-icon").show();
		jQuery(".score-tr-"+attemptId+ " .score-textbox").hide();
	}

	function updateAttemptData(event, attemptId, score, TotalMarks)
	{
		if (score < 0 || isNaN(score))
		{
			var msg = Joomla.Text._('COM_TJLMS_ENTER_NUMERNIC_MARKS');
			jQuery(".score-tr-"+attemptId+ " .score-textbox").val('');
			jQuery(".score-tr-"+attemptId+ " .score-textbox").focus();
			alert(msg);

			return false;
		}

		if (event.which == 13)
		{
			if (parseInt(score,10) > parseInt(TotalMarks,10))
			{
				var msg = Joomla.Text._('COM_TJLMS_ENTER_MARKS_GRT_TOTALMARKS');
				alert(msg);

				jQuery(".score-tr-"+attemptId+ " .score-textbox").val('');
				jQuery(".score-tr-"+attemptId+ " .score-textbox").focus();

				return false;
			}

			var isSure = confirm("Are you sure");

			if (isSure == true)
			{
				jQuery.ajax({
					url:"index.php?option=com_tjlms&task=attemptreport.updateAttemptData",
					type: "POST",
					dataType: "json",
					data:{attemptId:attemptId, score:score},
					success: function(data)
					{
						jQuery(".score-tr-"+attemptId+ " .user-score").show();
						jQuery(".score-tr-"+attemptId+ " .user-score").html(data.score);
						jQuery(".score-tr-"+attemptId+ " .marks-edit-icon").show();
						jQuery(".score-tr-"+attemptId+ " .score-textbox").hide();

						if (data.score < 0 || isNaN(score))
						{
							var msg = Joomla.Text._('COM_TJLMS_ENTER_NUMERNIC_MARKS');
							alert(msg);
						}
						else
						{
							var msg = Joomla.Text._('COM_TJLMS_UPDATED_MARKS_SUCCESSFULLY');
							alert(msg);
						}
					}
				});
			}

			return false;
		}
	}

	function enableTextToChangeMarks(trId)
	{
		jQuery(".score-tr-"+trId+ " .user-score").hide();
		jQuery(".score-tr-"+trId+ " .marks-edit-icon").hide();
		jQuery(".score-tr-"+trId+ " .score-textbox").show();
		jQuery(".score-tr-"+trId+ " .score-textbox").css("display","inline-block");
		jQuery(".score-tr-"+trId+ " .score-textbox").focus();
	}
	var origSdate = '<?php echo $this->state->get('filter.attempt_starts')?>';
	var origEdate = '<?php echo $this->state->get('filter.attempt_ends')?>';
	function submitFromCalendar(elem)
	{
		return validateSubmit(true,elem);
	}
	function validateSubmit(fromCalendar,elem)
	{
		var valid = checkValidCalVal();
		if(fromCalendar && (valid || ((origSdate && origSdate != document.getElementById("filter_attempt_starts").value) || (origEdate && origEdate != document.getElementById("filter_attempt_ends").value))))
		{
			valid = checkValidStartEnd(elem);
			if(valid)
			{
				jQuery("#adminForm").submit();
			}
		}
		else if(!valid)
		{
			dispEnqueueMessage(Joomla.Text._('COM_TJLMS_ATTEMPTREPORT_INVALID_DATE_FORMAT'),'invalid_dates');
		}
		return true;
	}
	function checkValidCalVal()
	{
		var valid = true;
		var validStart = document.formvalidator.validate(document.getElementById("filter_attempt_starts"));

		if(!validStart)
		{
			document.getElementById("filter_attempt_starts").value='';
			valid = false;
		}
		else if(startDate = document.getElementById("filter_attempt_starts").value)
		{
			sdTimeStamp = getTimeStampFromDate(startDate);
		}

		var validEnd = document.formvalidator.validate(document.getElementById("filter_attempt_ends"));

		if(!validEnd)
		{
			document.getElementById("filter_attempt_ends").value='';
			valid = false;
		}

		return valid;
	}
	function checkValidStartEnd(changedElem)
	{
		var valid = true;
		var startDate, sdTimeStamp, endDate, edTimeStamp;
		var validStart = document.formvalidator.validate(document.getElementById("filter_attempt_starts"));

		if(startDate = document.getElementById("filter_attempt_starts").value)
		{
			sdTimeStamp = getTimeStampFromDate(startDate);
		}

		if(endDate = document.getElementById("filter_attempt_ends").value)
		{
			edTimeStamp = getTimeStampFromDate(endDate);
		}

		if(changedElem && sdTimeStamp && edTimeStamp)
		{
			if(sdTimeStamp > edTimeStamp)
			{
				document.getElementById("filter_attempt_starts").value=origSdate;
				document.getElementById("filter_attempt_ends").value=origEdate;
				dispEnqueueMessage(Joomla.Text._('COM_TJLMS_ATTEMPTREPORT_DATE_RANGE_VALIDATION'),'starts_ends');
				valid = false;
			}
		}

		return valid;
	}
</script>

<?php

//Joomla Component Creator code to allow adding non select list filters
if (!empty($this->extra_sidebar))
{
	$this->sidebar .= $this->extra_sidebar;
}

?>


<div class="<?php echo COM_TJLMS_WRAPPER_DIV ?> attemptfiler">

<?php $usedAsPopupReport = $input->get('usedAsPopupReport', '0', 'INT'); ?>

<?php if ($usedAsPopupReport == 1):
			$tmpl = '&tmpl=component&usedAsPopupReport=1';
	endif;
?>
<form action="<?php echo Route::_('index.php?option=com_tjlms&view=attemptreport' . $tmpl); ?>" method="post" name="adminForm" id="adminForm" class="form-validate" onsubmit="return validateSubmit();">

	<?php

		if ($usedAsPopupReport == 0)
		{
			ob_start();
			include JPATH_BASE . '/components/com_tjlms/layouts/header.sidebar.php';
			$layoutOutput = ob_get_contents();
			ob_end_clean();
			echo $layoutOutput;

			echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
		}
	?> <!--// HTMLHelpersidebar for menu ends-->


	<?php if(JVERSION < '3.0'):	?>
		<div id="filter-bar" class="btn-toolbar">
			<div class="filter-search btn-group pull-left">
				<input type="text" name="filter_search" id="filter_search" placeholder="<?php echo Text::_('JSEARCH_FILTER_BY_USER_NAME'); ?>" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo Text::_('JSEARCH_FILTER'); ?>" />
			</div>
			<div class="btn-group pull-left">
				<button class="btn hasTooltip" type="submit" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i></button>
				<button class="btn hasTooltip" type="button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>" onclick="document.id('filter_search').value='';this.form.submit();"><i class="icon-remove"></i></button>
			</div>
			<div class="pull-right">
				<?php
					echo HTMLHelper::_('select.genericlist', $this->sstatus, "filter_published", 'class="" size="1" onchange="document.adminForm.submit();" name="filter_published"', "value", "text", $this->state->get('filter.state'));
				?>
			</div>
		</div>

	<?php endif; ?>


	<div class="clearfix"> </div>

	<?php if (empty($this->items)) : ?>
				<div class="alert alert-no-items">
					<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
				</div>
			</div><!--j-main-container ends-->
		</div> <!--CLose wrapper div as we return from here-->
		<?php return; ?>
	<?php endif;  ?>
	<?php if($usedAsPopupReport == 1){ ?>
		<h3><?php echo Text::_('COM_TJLMS_COURSE_REPORT_DASHBOARD'); ?></h3>
		<hr/>
		<?php } ?>
	<div class="table-container">
	 <div class="table-responsive">
		<table class="table table-striped left_table" id="usersList">
			<thead>
				<tr>
					<?php
					if ($usedAsPopupReport == 0)
					{
						?>
					<th width="1%" class="hidden-phone">
						<input type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
					</th>
					<?php
					}
					?>
					<th class='left'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ATTEMPTREPORT_ID', 'lt.attempt', $listDirn, $listOrder); ?>
					</th>
					<?php
					if ($usedAsPopupReport == 0)
					{
						?>
					<th class='left'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ATTEMPTREPORT_COURSENAME', 'c.title', $listDirn, $listOrder); ?>
					</th>
					<?php
					}
					?>
					<th class='left'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ATTEMPTREPORT_NAME', 'l.title', $listDirn, $listOrder); ?>
					</th>

					<th class='left'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_REPORT_USERNAME', 'u.name', $listDirn, $listOrder); ?>
					</th>

					<th class='left'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_REPORT_USERUSERNAME', 'u.username', $listDirn, $listOrder); ?>
					</th>

					<th class='left'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_REPORT_USEREMAIL', 'u.email', $listDirn, $listOrder); ?>
					</th>

					<th class='left'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ATTEMPTREPORT_IDEAL_TIME', 'l.ideal_time', $listDirn, $listOrder); ?>
					</th>

					<th class='left'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ATTEMPTREPORT_TIMESPENT', 'lt.time_spent', $listDirn, $listOrder); ?>
					</th>

					<th class='left' width="10%">
						<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ATTEMPTREPORT_STATUS', 'lt.lesson_status', $listDirn, $listOrder); ?>
					</th>
					<th class='left'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ATTEMPTREPORT_SCORE', 'lt.score', $listDirn, $listOrder); ?>
					</th>
					<th class='left'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ATTEMPTREPORT_LASTACCESS', 'lt.last_accessed_on', $listDirn, $listOrder); ?>
					</th>
					<th class='left'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ATTEMPTREPORT_MODIFIED_DATE', 'lt.modified_date', $listDirn, $listOrder); ?>
					</th>
					<th class='left'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ATTEMPTREPORT_STATE', 'lt.attempt_state', $listDirn, $listOrder); ?>
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
				$ordering   = ($listOrder == 'a.ordering');
				$canCreate	= $user->authorise('core.create',		'com_tjlms');
				$canEdit	= $user->authorise('core.edit',			'com_tjlms');
				$canCheckin	= $user->authorise('core.manage',		'com_tjlms');
				$canChange	= $user->authorise('core.edit.state',	'com_tjlms');
				?>
				<tr class="row<?php echo $i % 2; ?>">
					<?php
					if ($usedAsPopupReport == 0)
					{
						?>
					<td class="center hidden-phone">
						<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
					</td>
					<?php
					}
					?>
					<td>
						<?php echo $item->attempt; ?>
					</td>

					<?php
					if ($usedAsPopupReport == 0)
					{
						?>
					<td>
						<?php echo $item->title; ?>
					</td>
					<?php
					}
					?>

					<td>
						<?php echo $item->lesson_title; ?>
					</td>

					<td>
						<?php echo $item->user_name; ?>
					</td>

					<td>
						<?php echo $item->user_username; ?>
					</td>

					<td>
						<?php echo $item->useremail; ?>
					</td>

					<td>
						<?php echo $item->ideal_time; ?>
					</td>

					<td>
						<?php echo $item->time_spent; ?>
					</td>

					<td width="10%">
						<?php $lessonStatus = array(); ?>
						<?php
							$lessonStatus[] = HTMLHelper::_('select.option', 'started', Text::_('COM_TJLMS_FILTER_STATUS_STARTED'));
							$lessonStatus[] = HTMLHelper::_('select.option', 'incomplete', Text::_('COM_TJLMS_LESSONSTATUS_INCOMPLETE'));
							$lessonStatus[] = HTMLHelper::_('select.option', 'completed', Text::_('COM_TJLMS_FILTER_STATUS_COMPLETED'));

							$lessonStatus[] = HTMLHelper::_('select.option', 'passed', Text::_('COM_TJLMS_FILTER_STATUS_PASSED'));
							$lessonStatus[] = HTMLHelper::_('select.option', 'failed', Text::_('COM_TJLMS_FILTER_STATUS_FAILED'));
							$lessonStatus[] = HTMLHelper::_('select.option', 'AP', Text::_('COM_TMT_ASSESSMENT_PENDING'));
							?>

						<?php echo HTMLHelper::_('select.genericlist',$lessonStatus,"lesson_status".$i,'class="pad_status input-small" id="lesson_status" onChange="changeLessonStatus('.$item->id.',this.value);"',"value","text",$item->lesson_status); ?>
					</td>

					<td width="6%" class="score-tr-<?php echo $item->id; ?>">
						<?php

						if ($item->format == 'quiz' || $item->format == 'exercise' || $item->format == 'feedback')
						{
							?>

							<a class="user-score cursorpointer" target="_blank" onclick="window.open('<?php echo Route::_(Uri::root() . 'index.php?option=com_tmt&view=answersheet&tmpl=component&adminKey=' . $this->adminKey . '&id=' . $item->test_id . '&ltId=' . $item->id . '&candid_id=' . $item->user_id . '&isAdmin=1', false) ?>', 'mywin', 'left=20, top=20, width=1200, height=800, toolbar=1, resizable=0');" >

						<?php if ($item->format == 'feedback')
								{
									echo Text::_("COM_TJLMS_VIEW_REPORT");
								}
								else
								{
									echo $item->score;
								}
							?>
							</a>

							<?php if ($item->format != 'feedback'):?>
								<span class="pull-right marks-edit-icon" onclick="enableTextToChangeMarks('<?php echo $item->id?>')"><i class="icon-pencil"></i></span>
								<input type="text"  class="input-small score-textbox" style="display:none"  onblur="resetElement(event,'<?php echo $item->id; ?>',this.value);" onkeypress="return updateAttemptData(event,'<?php echo $item->id; ?>',this.value,'<?php echo $item->total_marks ?>');">
							<?php endif;?>
							<?php
						}
						else
						{
							?>
							<span class="" ><?php echo $item->score;	?></span>
							<!--<span class="pull-right marks-edit-icon" onclick="enableTextToChangeMarks('<?php echo $item->id?>')"><i class="icon-pencil"></i></span>
							<input type="text"  class="input-small score-textbox" style="display:none" onkeypress="return updateAttemptData(event,'<?php echo $item->id; ?>',this.value);">-->
						<?php
						}
						?>
					</td>

					<td>
						<?php echo $item->last_accessed_on; ?>
					</td>

					<td>
						<?php echo $item->modified_date; ?>
					</td>
					<td>
						<?php echo ($item->lesson_track_id) ? Text::_("COM_TJLMS_ATTEMPTREPORT_STATE_EXPIRED") : Text::_("COM_TJLMS_ATTEMPTREPORT_STATE_ACTIVE"); ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	 </div>
	</div>
		<input type="hidden" id="task" name="task" value="" />
		<input type="hidden" id='attempt_id' name="attempt_id" value="" />
		<input type="hidden" id='lesson_status' name="lesson_status" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<input type="hidden" name="usedAsPopupReport" value="<?php echo $usedAsPopupReport; ?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div><!--j-main-container ENDS-->
	</div><!--row-fluid ENDS-->
</form>

</div><!--wrapper ends-->


