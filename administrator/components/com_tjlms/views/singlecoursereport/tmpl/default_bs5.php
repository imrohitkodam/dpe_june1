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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('behavior.multiselect');

HTMLHelper::_('behavior.modal');
jimport('techjoomla.common');
$this->techjoomlacommon = new TechjoomlaCommon;
$lmsparams   = ComponentHelper::getParams('com_tjlms');
$date_format_show = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');
//print_r($this->items);die;
$listOrder	= $this->state->get('list.ordering');
$listDirn	= $this->state->get('list.direction');
?>
<script type="text/javascript">
	jQuery(document).ready(function(){
		jQuery('.input-append .active').removeClass('active');

		if (jQuery("#filter_coursefilter").val() || jQuery("#filter_state").val() || jQuery("#filter_categoryfilter").val() || jQuery("#filter_accessfilter").val() || jQuery("#filter_userfilter").val() || jQuery("#filter_enroll_starts").val() || jQuery("#filter_enroll_ends").val())
		{
			jQuery('.js-stools-container-filters').show();
			jQuery('.js-stools-btn-filter').addClass('btn-primary')
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
</script>


<div class="<?php echo COM_TJLMS_WRAPPER_DIV ?>">

<form action="<?php echo Route::_('index.php?option=com_tjlms&view=singlecoursereport'); ?>" method="post" name="adminForm" id="adminForm">

	<?php
			ob_start();
			include JPATH_BASE . '/components/com_tjlms/layouts/header.sidebar.php';
			$layoutOutput = ob_get_contents();
			ob_end_clean();
			echo $layoutOutput;
?>
	<?php		echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));

	?> <!--// JHtmlsidebar for menu ends-->


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
		<?php
		// Filter by Course
		$coursefilter = $this->state->get('filter.coursefilter');

		?>
					<?php
					if (empty($coursefilter))
					{
						echo Text::_('COM_TJLMS_SELECT_COURSE_MESSAGE');
					}
					else
					{
						echo Text::_('JGLOBAL_NO_MATCHING_RESULTS');
					}
					?>
				</div>
		</div> <!--CLose wrapper div as we return from here-->
		<?php return; ?>
	<?php endif;  ?>

	<table class="table table-striped" id="usersList">
		<thead>
			<tr>
						<th class='left' colspan="10"></th>

				<?php
				$count_lessons = $this->items['0']->totallessonsattempted;
				if (!empty($this->items['0']->lessonheader))
				{
					$less_headers = $this->items['0']->lessonheader;


					foreach($less_headers AS $keyless1=>$lessonheader1)
					{
					?>

					<th class='left' colspan="3"><?php echo $keyless1;?></th>
					<?php
					}
				}
					?>
			</tr>
			<tr>
				<?php echo $displayToggel = ''; ?>



				<th class='left'><?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_EMP_NAME', 'user_name', $listDirn, $listOrder); ?></th>
				<th class='left'><?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_EMP_USERNAME', 'username', $listDirn, $listOrder); ?></th>
				<th class='left'><?php echo Text::_('COM_TJLMS_EMP_EMAIL'); ?></th>
				<th class='left'><?php echo Text::_('COM_TJLMS_EMP_GROUP'); ?></th>
				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COURSE_CAT', 'cat.title', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_USER_ENROLLED_ON', 'eu.enrolled_on_time', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>Due Date</th>
				<th class='left'><?php echo Text::_('COM_TJLMS_COURSE_COMPLETED_DATE'); ?></th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COMPLETION', 'completion', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_REPORT_TIMESPENT', 'totaltimespent', $listDirn, $listOrder); ?>
				</th>
			<?php
			if (!empty($less_headers))
			{
				foreach($less_headers AS $keyless1=>$lessonheader1)
				{
					?>
					<th class='right'><?php echo Text::_( 'COM_TJLMS_LESSON_SCORE_STUDENT');?></th>
					<th class='left'><?php echo Text::_( 'COM_TJLMS_LESSON_TIME_SPENT_STUDENT');?></th>
					<th class='left'><?php echo Text::_( 'COM_TJLMS_LESSON_STATUS_STUDENT');?></th>
					<?php
				}
			}
			?>
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

		?>
			<tr class="row<?php echo $i % 2; ?>">


				<td><?php echo $item->user_name; ?></td>
				<td><?php echo $item->username; ?></td>
				<td><?php echo $item->email; ?></td>
				<td><?php echo $item->groups; ?></td>
				<td><?php echo $item->cat_title; ?></td>
				<td><?php

				if ($item->enrolled_on_time == '-')
				{
					echo $item->enrolled_on_time;
				}
				else
				{
					if(!empty($item->enrolled_on_time))
						echo  $this->techjoomlacommon->getDateInLocal($item->enrolled_on_time, 0, $date_format_show);
					else
						echo '-';
				}
				 ?></td>
				<td><?php

				if ($item->course_due_date == '-')
				{
					echo $item->course_due_date;
				}
				else
				{
					if(!empty($item->course_due_date))
						echo  $this->techjoomlacommon->getDateInLocal($item->course_due_date, 0, $date_format_show);
					else
						echo "-";
				}
				?></td>
				<td><?php

				if(!empty($item->completion_date))
				{
					if ($item->completion_date == '-')
					{
						echo $item->completion_date;
					}
					else
					{

						echo  $this->techjoomlacommon->getDateInLocal($item->completion_date, 0, $date_format_show);
					}
				}
				else
				{
					echo '-';
				}

				?></td>
				<td>
					<a href="<?php echo Uri::root() . 'administrator/index.php?option=com_tjlms&view=lessonreport&filter[coursefilter]=' . $item->course_id . '&filter[userfilter]=' .  $item->user_id; ?>"><?php echo $item->completion . '%'; ?></a>
				</td>
				<td><?php echo $item->totaltimespent; ?></td>

				<?php
				if (!empty($this->items['0']->lessonheader))
				{
					foreach($this->items['0']->lessonheader AS $keyless=>$lessonheader)
					{
				?>
					<td align="right"><?php if (!empty($item->lessondata[$keyless]->score)) echo $item->lessondata[$keyless]->score; else echo 0;?></td>
					<td><?php if (!empty($item->lessondata[$keyless]->timeSpentOnLesson)) echo $item->lessondata[$keyless]->timeSpentOnLesson; ?></td>
					<td><?php if (!empty($item->lessondata[$keyless]->lesson_status)) echo $item->lessondata[$keyless]->lesson_status; ?></td>
				<?php
					}
				}
				?>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

		<input type="hidden" id="task" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo HTMLHelper::_('form.token'); ?>

</form>

</div><!--wrapper ends-->


