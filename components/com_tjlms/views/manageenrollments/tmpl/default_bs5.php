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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
HTMLHelper::_('behavior.formvalidator');

if (JVERSION >= '3.0')
{
	HTMLHelper::_('bootstrap.tooltip');
	HTMLHelper::_('formbehavior.chosen', 'select');
	HTMLHelper::_('behavior.multiselect');
}
HTMLHelper::_('bootstrap.renderModal');

// Import CSS
HTMLHelper::_('stylesheet', 'components/com_tjlms/assets/css/tjlms.css');

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
$courseId = $input->get('course_id','','INT');
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
	jQuery('a.studentsReport').attr('rel','{handler: "iframe", size: {x: '+(width-(width*0.10))+', y: '+(height-(height*0.10))+'}}');
});
</script>


<?php
$enrolledclass ="";
if (!empty($courseId))
	{
		$enrolledclass= "manageenrollpopup";
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
		<h3><?php echo Text::sprintf('COM_TJLMS_ENROLLED_USERS_FOR_COURSE',$this->courseInfo->title); ?>
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


			include JPATH_BASE . '/components/com_tjlms/views/manageenrollments/tmpl/import.php';
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
				if (!$courseId)
				{
					//echo JHtml::_('select.genericlist', $this->coursefilter, "coursefilter", 'class="" size="1" onchange="document.adminForm.submit();" name="coursefilter"', "value", "text", $this->state->get('filter.coursefilter'));
				}
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

	<div class="tjlms-tbl">
			<table class="table table-striped left_table" id="usersList">
				<thead>
					<tr>
						<?php
							$displayToggel = '';

							if ($courseId)
							{
								$displayToggel = 'display:none';
							}


						if (isset($this->items[0]->ordering)): ?>

							<th width="1%" class="nowrap center hidden-phone" style="<?php echo $displayToggel; ?>">
								<?php echo HTMLHelper::_('grid.sort', '<i class="icon-menu-2"></i>', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
							</th>

						<?php endif; ?>

							<th width="1%" class="hidden-phone" style="<?php echo $displayToggel; ?>">
								<input type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
							</th>


						<?php if (!$courseId):	?>
							<?php if (isset($this->items[0]->state)): ?>

								<th width="1%" class="nowrap center">
									<?php echo HTMLHelper::_('grid.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
								</th>

							<?php endif; ?>
						<?php endif; ?>


							<th class='left'>
							<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ENROLMENT_USER_NAME', 'u.name', $listDirn, $listOrder); ?>
							</th>
							<?php if (!$courseId)
							{	?>
							<th class='left'>
							<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_MANAGEENROLLMENTS_COURSE_ID', 'a.course_id', $listDirn, $listOrder); ?>
							</th>
							<?php } ?>
							<th class='left'>
							<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ENROLMENT_USER_USERNAME', 'u.username', $listDirn, $listOrder); ?>
							</th>

							<th class='left'>
								<?php	echo JHTML::tooltip(Text::_('COM_TJLMS_ENROLMENT_GROUP_TITLE'), '','', Text::_('COM_TJLMS_ENROLMENT_GROUP_TITLE')); ?>
							</th>


						<?php
						if (!$courseId)
						{
							if (isset($this->items[0]->id)): ?>
								<th width="1%" class="nowrap center hidden-phone">
									<?php echo HTMLHelper::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
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


						if (isset($this->items[0]->ordering)): ?>
							<td class="order nowrap center hidden-phone" style="<?php echo $displayToggel; ?>">
							<?php if ($canChange) :
								$disableClassName = '';
								$disabledLabel	  = '';
								if (!$saveOrder) :
									$disabledLabel    = Text::_('JORDERINGDISABLED');
									$disableClassName = 'inactive tip-top';
								endif; ?>
								<span class="sortable-handler hasTooltip <?php echo $disableClassName?>" title="<?php echo $disabledLabel?>">
									<i class="icon-menu"></i>
								</span>
								<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->ordering;?>" class="width-20 text-area-order " />
							<?php else : ?>
								<span class="sortable-handler inactive" >
									<i class="icon-menu"></i>
								</span>
							<?php endif; ?>
							</td>
						<?php endif;	?>
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
					<td>
						<?php echo $item->name; ?>
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
						<?php echo $item->groups; ?>
					</td>


					<?php
					if (!$courseId)
					{
						if (isset($this->items[0]->id)): ?>
							<td class="center hidden-phone">
								<?php echo (int) $item->id; ?>
							</td>
						<?php endif;
					}
					else
					{
						$studenCoursereport = 'index.php?option=com_tjlms&view=lessonreport&filter[coursefilter]=' . $item->course_id . '&filter[userfilter]=' . $item->user_id . '&usedAsPopupReport=1&tmpl=component';
						//$studenCoursereport = JUri::root().'index.php?option=com_tjlms&view=reports&tmpl=component&course_id='.$item->course_id.'&stuid='.$item->user_id.'&Itemid='.$this->studentCourseDashboardItemid;
						?>
						<td>
								<a class="modal studentsReport" onclick=opentjlmsSqueezeBox("<?php echo $studenCoursereport; ?>") >
									<?php echo Text::_( 'COM_TJLMS_STUDENT_COURSE_REPORT_DASHBOARD' ); ?>
								</a>
						</td>
			<?php	}
					?>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div><!--j-main-container ENDS-->
	</div><!--row-fluid ENDS-->
</form>

</div><!--wrapper ends-->


