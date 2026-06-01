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
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Uri\Uri;

if (JVERSION >= '3.0')
{
	HTMLHelper::_('bootstrap.tooltip');
	HTMLHelper::_('formbehavior.chosen', 'select');
	HTMLHelper::_('behavior.multiselect');
}

jimport('techjoomla.common');

$this->techjoomlacommon = new TechjoomlaCommon;
$lmsparams   = ComponentHelper::getParams('com_tjlms');
$date_format_show = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

$user	= Factory::getUser();
$userId	= $user->get('id');
$listOrder	= $this->state->get('list.ordering');
$listDirn	= $this->state->get('list.direction');
$canOrder	= $user->authorise('core.edit.state', 'com_tjlms');
$saveOrder	= $listOrder == 'a.ordering';
$input = Factory::getApplication()->input;

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_tjlms&task=manageenrollments.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'usersList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

$coursefilter	= $this->state->get('filter.coursefilter');
?>
<script type="text/javascript">
	jQuery(document).ready(function(){
		if (jQuery("#filter_coursefilter").val() || jQuery("#filter_state").val() || jQuery("#filter_userfilter").val() || jQuery("#filter_lesonformat").val())
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
</script>

<?php

//Joomla Component Creator code to allow adding non select list filters
if (!empty($this->extra_sidebar))
{
	$this->sidebar .= $this->extra_sidebar;
}
?>


<div class="<?php echo COM_TJLMS_WRAPPER_DIV ?>">

<?php $usedAsPopupReport = $input->get('usedAsPopupReport', '0', 'INT'); ?>

<?php if ($usedAsPopupReport == 1):
			$tmpl = '&tmpl=component&usedAsPopupReport=1';
	endif;
?>

<form action="<?php echo Route::_('index.php?option=com_tjlms&view=lessonreport' . $tmpl); ?>" method="post" name="adminForm" id="adminForm" class="admin-report">

	<h3><?php echo Text::_('COM_TJLMS_COURSE_REPORT_DASHBOARD'); ?></h3>
	<hr/>

	<?php
		if ($usedAsPopupReport == 0)
		{
			ob_start();
			include JPATH_BASE . '/components/com_tjlms/layouts/header.sidebar.php';
			$layoutOutput = ob_get_contents();
			ob_end_clean();
			echo $layoutOutput;
		}

		if (Factory::getUser()->authorise('core.admin'))
		{
			echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
		}

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
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
	<?php else: ?>
<div class="tjlms-tbl">
	<table class="table table-striped left_table" id="usersList">
		<thead>
			<tr>
				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_LESSONREPORT_ID', 'lt.lesson_id', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_LESSONREPORT_NAME', 'l.title', $listDirn, $listOrder); ?>
				</th>
				<?php
				if ($usedAsPopupReport == 0)
				{
					?>
					<th class='left'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_LESSONREPORT_COURSENAME', 'c.title', $listDirn, $listOrder); ?>
					</th>
				<?php
				}
				?>
				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_LESSONREPORT_STARTDATE', 'l.start_date', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_LESSONREPORT_ENDDATE', 'l.end_date', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_REPORT_USERNAME', 'user_id', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_REPORT_USERUSERNAME', 'user_username', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_REPORT_USEREMAIL', 'user_email', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_LESSONREPORT_ALLOWEDATTEMPTS', 'no_of_attempts', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_LESSONREPORT_ATTEMPTSMADE', 'attemptsDone', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_LESSONREPORT_EXPIRED_ATTEMPTS', 'expired_attempts', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_LESSONREPORT_STATUS', 'lt.lesson_status', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_LESSONREPORT_TIMESPENT', 'timeSpentOnLesson', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_LESSONREPORT_SCORE', 'score', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_LESSONREPORT_GRADINGMETHOD', 'l.attempts_grade', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_LESSONREPORT_COMSIDERMARKS', 'l.consider_marks', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_LESSONREPORT_FORMAT', 'l.format', $listDirn, $listOrder); ?>
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
				<td>
					<?php echo $item->lesson_id; ?>
				</td>

				<td>
					<?php echo $item->title; ?>
				</td>

				<?php
				if ($usedAsPopupReport == 0)
				{
					?>
					<td>
						<?php echo $item->courseTitle; ?>
					</td>
				<?php
				}
				?>
				<td>
					<?php
					if (!empty($item->start_date))
					{
						if ($item->start_date == '0000-00-00 00:00:00')
						{
							$item->start_date = '-';
							echo $item->start_date;
						}

						if ($item->start_date != '-')
						{
							echo $this->techjoomlacommon->getDateInLocal($item->start_date, 0, $date_format_show);
						}

					}
					?>
				</td>

				<td>
					<?php
					if (!empty($item->end_date))
					{
						if ($item->end_date == '0000-00-00 00:00:00')
						{
							$item->end_date = '-';
							echo $item->end_date;
						}

						if ($item->end_date != '-')
						{
							echo $this->techjoomlacommon->getDateInLocal($item->end_date, 0, $date_format_show);
						}
					}
					?>
				</td>
				<td>
					<?php echo Factory::getUser($item->user_id)->name; ?>
				</td>
				<td>
					<?php echo $item->user_username; ?>
				</td>

				<td>
					<?php echo $item->user_email; ?>
				</td>

				<td>


					<?php if ($item->no_of_attempts == 0):	?>
						<?php echo Text::_('COM_TJLMS_UNLIMITED');	?>
					<?php else: ?>
						<?php echo $item->no_of_attempts; ?>
					<?php endif; ?>
				</td>

				<td>
					<?php
					$additionalParam = '';

					if ($usedAsPopupReport == 1)
					{
						$additionalParam = '&usedAsPopupReport=1&tmpl=component';
					}
					?>

					<a href="<?php echo Uri::root() . 'administrator/index.php?option=com_tjlms&view=attemptreport' . $additionalParam . '&filter[userfilter]=' . $item->user_id . '&filter[lessonfilter]=' .  $item->lesson_id; ?>"><?php echo $item->attemptsDone; ?></a>
				</td>
				<td>
					<?php echo $item->expired_attempts; ?>

					<a href="<?php echo JUri::root() . 'administrator/index.php?option=com_tjlms&view=attemptreport&layout=modal' . $additionalParam . '&filter[userfilter]=' . $item->user_id . '&filter[lessonfilter]=' .  $item->lesson_id . '&filter[attemptState]=0'?>"><?php echo $item->attemptsDone; ?></a>

				</td>
				<td>
					<?php echo $item->lesson_status; ?>
				</td>
				<td>
					<?php echo $item->timeSpentOnLesson; ?>
				</td>
				<td>
					<?php echo $item->score; ?>
				</td>
				<td>
					<?php
					switch ($item->attempts_grade)
					{
						case '0':
							echo Text::_('COM_TJLMS_HIGHEST_ATTEMPT');
							break;
						case '1':
							echo Text::_('COM_TJLMS_AVERAGE_ATTEMPT');
							break;
						case '2':
							echo Text::_('COM_TJLMS_FIRST_ATTEMPT');
							break;
						case '3':
							echo Text::_('COM_TJLMS_LAST_COMPLETED_ATTEMPT');
							break;
					}
				?>
				</td>
				<td>
					<?php $activeValue = Text::_('JNO');	?>
					<?php if ($item->consider_marks == 1):	?>
						<?php $activeValue = Text::_('JYES');	?>
					<?php endif; ?>

					<?php echo $activeValue;	?>
				</td>
				<td>
					<?php echo $item->format; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
<?php endif;  ?>
		<input type="hidden" id="task" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<input type="hidden" name="filter[coursefilter]" value="<?php echo $coursefilter; ?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div><!--j-main-container ENDS-->
	</div><!--row-fluid ENDS-->
</form>

</div><!--wrapper ends-->


