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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('behavior.multiselect');

$listOrder	= $this->state->get('list.ordering');
$listDirn	= $this->state->get('list.direction');

?>
<script type="text/javascript">
	jQuery(document).ready(function(){
		if (jQuery("#filter_coursefilter").val() || jQuery("#filter_state").val() || jQuery("#filter_categoryfilter").val() || jQuery("#filter_accessfilter").val() || jQuery("#filter_userfilter").val())
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


<div class="<?php echo COM_TJLMS_WRAPPER_DIV ?>">

<form action="<?php echo Route::_('index.php?option=com_tjlms&view=studentcoursereport'); ?>" method="post" name="adminForm" id="adminForm">

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
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COURSE_NAME', 'c.title', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COURSE_CAT', 'cat.title', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ACL_GROUP', 'access_level_title', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_FORM_LBL_COURSE_CERTIFICATE_TERM', 'certificate_term', $listDirn, $listOrder); ?>
				</th>

				<!--th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COURSE_TYPE', 'c.type', $listDirn, $listOrder); ?>
				</th-->

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_REPORT_USERNAME', 'user_name', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_REPORT_USERUSERNAME', 'user_username', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_REPORT_USEREMAIL', 'useremail', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_USER_END_DATE', 'eu.end_time', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COMPLETION', 'completion', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_REPORT_TIMESPENT', 'totaltimespent', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COURSE_ID', 'eu.course_id', $listDirn, $listOrder); ?>
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

		<?php foreach ($this->items as $i => $item) : ?>
			<tr class="row<?php echo $i % 2; ?>">
				<td class="center hidden-phone" style="<?php echo $displayToggel; ?>">
					<?php echo HTMLHelper::_('grid.id', $i, $item->course_id); ?>
				</td>

				<td>
					<?php echo $item->title; ?>
				</td>

				<td>
					<?php echo $item->cat_title; ?>
				</td>

				<td>
					<?php echo $item->access_level_title; ?>
				</td>

				<td>
					<?php echo $item->certificate_term; ?>
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
					<?php echo $item->end_time; ?>
				</td>

				<td>
					<a href="<?php echo Uri::root() . 'administrator/index.php?option=com_tjlms&view=lessonreport&filter[coursefilter]=' . $item->course_id . '&filter[userfilter]=' .  $item->user_id; ?>"><?php echo $item->completion . '%'; ?></a>
				</td>

				<td>
					<?php echo $item->totaltimespent; ?>
				</td>

				<td>
				<?php echo $item->course_id; ?>
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


