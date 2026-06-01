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
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');

if (JVERSION >= '3.0')
{
	HTMLHelper::_('bootstrap.tooltip');
	HTMLHelper::_('formbehavior.chosen', 'select');
	HTMLHelper::_('behavior.multiselect');
}
JHTML::_('behavior.modal');

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

?>
<script type="text/javascript">
	jQuery(document).ready(function(){
		if (jQuery("#filter_coursefilter").val() || jQuery("#filter_state").val())
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

<form action="<?php echo Route::_('index.php?option=com_tjlms&view=userreport'); ?>" method="post" name="adminForm" id="adminForm">

	<?php
			ob_start();
			include JPATH_BASE . '/components/com_tjlms/layouts/header.sidebar.php';
			$layoutOutput = ob_get_contents();
			ob_end_clean();
			echo $layoutOutput;

			echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));

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
			</div><!--j-main-container ends-->
		</div> <!--CLose wrapper div as we return from here-->
		<?php return; ?>
	<?php endif;  ?>

	<table class="table table-striped left_table" id="usersList">
		<thead>
			<tr>
				<?php echo $displayToggel = ''; ?>
				<th width="1%" class="hidden-phone" style="<?php echo $displayToggel; ?>">
					<input type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ENROLMENT_USER_ID', 'u.id', $listDirn, $listOrder); ?>
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
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ENROLMENT_TOTAL_COURSES_ENROLLED', 'enrolled_courses', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ENROLMENT_TOTAL_COURSES_COMPLETED', 'totalCompletedCourses', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ENROLMENT_TOTAL_COURSES_INCOMPLETED', 'inCompletedCourses', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ENROLMENT_TOTAL_PENDING_ENROLLED', 'pending_enrollment', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ENROLMENT_GROUP_TITLE', 'groups', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ENROLMENT_USER_BLOCKED', 'u.block', $listDirn, $listOrder); ?>
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
				<td class="center hidden-phone" style="<?php echo $displayToggel; ?>">
					<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
				</td>

				<td>
					<?php echo $item->id; ?>
				</td>

				<td>
					<?php echo $item->name; ?>
				</td>

				<td>
					<?php echo $item->username; ?>
				</td>

				<td>
					<?php echo $item->email; ?>
				</td>

				<td>
					<a class="enrolled_users_cnt" href="<?php echo Uri::root().'administrator/index.php?option=com_tjlms&view=coursereport&filter[userfilter]='.$item->id ?>"  ><?php echo $item->enrolled_courses; ?>
					</a>
				</td>

				<td>
					<?php echo $item->totalCompletedCourses; ?>
				</td>

				<td>
					<?php echo $item->inCompletedCourses; ?>
				</td>

				<td>
					<?php echo $item->pending_enrollment; ?>
				</td>

				<td>
					<?php echo $item->groups; ?>
				</td>

				<td>
					<?php $activeValue = Text::_('JYES');	?>
					<?php if ($item->block == 1):	?>
						<?php $activeValue = Text::_('JNO');	?>
					<?php endif; ?>

					<?php echo $activeValue;	?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

		<input type="hidden" id="task" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div><!--j-main-container ENDS-->
	</div><!--row-fluid ENDS-->
</form>

</div><!--wrapper ends-->


