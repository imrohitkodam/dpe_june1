<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla extensions@techjoomla.com
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;

jimport('techjoomla.common');

HTMLHelper::addIncludePath(JPATH_COMPONENT.'/helpers/html');

if (JVERSION >= '3.0')
{
	HTMLHelper::_('bootstrap.tooltip');
	HTMLHelper::_('behavior.multiselect');
}
$user      = Factory::getUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canOrder  = $user->authorise('core.edit.state', 'com_tjlms');
$saveOrder = $listOrder == 'a.ordering';
if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_tjlms&task=coupons.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'couponList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
$date_format_show = $this->lmsparams->get('date_format_show','Y-m-d H:i:s');
?>
<script type="text/javascript">
	Joomla.orderTable = function() {
		table     = document.getElementById("sortTable");
		direction = document.getElementById("directionTable");
		order     = table.options[table.selectedIndex].value;

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
if (!empty($this->extra_sidebar)) {
    $this->sidebar .= $this->extra_sidebar;
}
?>
<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?>">
<form action="<?php echo Route::_('index.php?option=com_tjlms&view=coupons'); ?>" method="post" name="adminForm" id="adminForm">
	<?php
			ob_start();
			include JPATH_BASE . '/components/com_tjlms/layouts/header.sidebar.php';
			$layoutOutput = ob_get_contents();
			ob_end_clean();
			echo $layoutOutput;

			echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
		?> <!--// JHtmlsidebar for menu ends-->
		<div class="clearfix"> </div>
		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
		<div class="tjlms-tbl table-responsive">
		<table class="table table-striped" id="couponList">
			<thead>
				<tr>
                <?php if (isset($this->items[0]->ordering)): ?>
					<th width="1%" class="nowrap center hidden-phone">
						<?php echo HTMLHelper::_('grid.sort', '<i class="icon-menu-2"></i>', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
					</th>
                <?php endif; ?>
					<th width="1%" class="hidden-phone">
						<input type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
					</th>
                <?php if (isset($this->items[0]->state)): ?>
					<th width="1%" class="nowrap center">
						<?php echo HTMLHelper::_('grid.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
					</th>
                <?php endif; ?>
				<th class='left'>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COUPONS_CREATED_BY', 'a.created_by', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COUPONS_NAME', 'a.name', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COUPONS_CODE', 'a.code', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COUPONS_VALUE', 'a.value', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COUPONS_VAL_TYPE', 'a.val_type', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COUPONS_MAX_USE', 'a.max_use', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COUPONS_MAX_PER_USER', 'a.max_per_user', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COUPONS_USED_COUNT', 'a.used_count', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COUPONS_COURSE', 'a.course_id', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php
					echo HTMLHelper::tooltip(Text::_('COM_TJLMS_COUPONS_COURSE_SUBSCRIPTION'), '', '', Text::_('COM_TJLMS_COUPONS_COURSE_SUBSCRIPTION'));
					?>
				</th>
				<th class='left'>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COUPONS_FROM_DATE', 'a.from_date', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COUPONS_EXP_DATE', 'a.exp_date', $listDirn, $listOrder); ?>
				</th>
                <?php if (isset($this->items[0]->id)): ?>
					<th width="1%" class="nowrap center hidden-phone">
						<?php echo HTMLHelper::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
					</th>
                <?php endif; ?>
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
				<?php if (isset($this->items[0]->ordering)): ?>
					<td class="order nowrap center hidden-phone">
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
                <?php endif; ?>
					<td class="center hidden-phone">
						<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
					</td>
                <?php if (isset($this->items[0]->state)): ?>
					<td class="center">
						<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'coupons.', $canChange, 'cb'); ?>
					</td>
                <?php endif; ?>

				<td>

					<?php echo $item->created_by; ?>
				</td>
				<td>
				<?php if (isset($item->checked_out) && $item->checked_out) : ?>
					<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'coupons.', $canCheckin); ?>
				<?php endif; ?>
				<?php if ($canEdit) : ?>
					<a href="<?php echo Route::_('index.php?option=com_tjlms&task=coupon.edit&id='.(int) $item->id); ?>">
					<?php echo $this->escape($item->name); ?></a>
				<?php else : ?>
					<?php echo $this->escape($item->name); ?>
				<?php endif; ?>
				</td>
				<td>

					<?php echo $item->code; ?>
				</td>
				<td>

					<?php echo (int) $item->value; ?>
				</td>
				<td>
					<?php
						if ($item->val_type == 0)
						{
							echo Text::_('COM_TJLMS_COUPON_FLAT');
						}
						else
						{
							echo Text::_('COM_TJLMS_COUPON_PERCENTAGE');
						}
					?>
				</td>
				<td>
					<?php
					if ($item->max_use == "")
					{
						echo Text::_('COM_TJLMS_COUPON_MAX_USE_UNLIMITED');
					}
					else
					{
						echo $item->max_use;
					}?>
				</td>
				<td>
					<?php
					if ($item->max_per_user == "")
					{
						echo Text::_('COM_TJLMS_COUPON_MAX_USE_UNLIMITED');
					}
					else
					{
						echo $item->max_per_user;
					}?>
				</td>
					<td>
					<?php echo $item->used_count; ?>
				</td>
				<td>
				<?php
					if($item->course_id)
					{
						$courses     = $this->tjlmsCoursesHelper->getCouponCourseId($item->course_id);
						$tempCourses = array();
						foreach ($courses as $course)
						{
							$tempCourses[] = $course->title;
						}
						if(!empty($tempCourses)){
							echo $this->escape(implode(', ',$tempCourses));
						}
					}
				  ?>
				</td>
				<td>
				<?php
					if ($item->subscription_id)
					{
						JLoader::import('components.com_tjlms.helpers.courses', JPATH_SITE);
						$tjlmsCoursesHelper = new TjlmsCoursesHelper;

						$subscriptions = explode(",", $item->subscription_id);
						$tempCourses   = array();

						foreach ($subscriptions as $subscription)
						{
							$subscriptionDetails = $tjlmsCoursesHelper->getPlanDetails($subscription);
							$tempCourses[]       = $subscriptionDetails->duration . " " . $subscriptionDetails->time_measure;
						}

						if (!empty($tempCourses))
						{
							echo $this->escape(implode(', ', $tempCourses));
						}
					}
				?>
				</td>
				<td>
					<?php echo ($item->from_date == '0000-00-00 00:00:00') ? '-' : $this->techjoomlacommon->getDateInLocal($item->from_date, 0, $date_format_show ); ?>
				</td>
				<td>
					<?php echo ($item->from_date == '0000-00-00 00:00:00') ? '-' : $this->techjoomlacommon->getDateInLocal($item->exp_date, 0, $date_format_show ); ?>
				</td>


                <?php if (isset($this->items[0]->id)): ?>
					<td class="center hidden-phone">
						<?php echo (int) $item->id; ?>
					</td>
                <?php endif; ?>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		</div>
		<?php endif; ?>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
		</div><!--j-main-container ENDS-->
		</div><!--row-fluid ENDS-->
	</form>
</div> <!--techjoomla-bootstrap-->


