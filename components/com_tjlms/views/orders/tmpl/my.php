<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

if (JVERSION >= '3.0')
{
	HTMLHelper::_('bootstrap.tooltip');
	HTMLHelper::_('behavior.multiselect');
}

HTMLHelper::_('bootstrap.renderModal', 'a.tjmodal');
$listOrder	= $this->state->get('list.ordering');
$listDirn	= $this->state->get('list.direction');
$canOrder	= $this->user->authorise('core.edit.state', 'com_tjlms');
$saveOrder	= $listOrder == 'a.ordering';

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_tjlms&task=orders.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'orderList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

?>
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
</script>
<div class="container-fluid tjBs3">
	<div>
		<h2><?php echo Text::_("COM_TJLMS_ORDERS")?></h2>
	</div>
	<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?>" class="row">
		<form action="" method="post" name="adminForm" id="adminForm">
			<div class="clearfix"></div>
			<?php
				echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
			?>

			<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items alert-info">
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
				<?php else : ?>

			<div class="clearfix"></div>
			<div class="table-responsive">
				<table class="table table-striped" id="orderList">
					<thead>
						<tr>
							<th class='left'>
							<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ORDERS_ORDER_ID', 'a.order_id', $listDirn, $listOrder); ?>
							</th>

							<th class='left'>
							<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ORDERS_COURSE_ID', 'a.course_id', $listDirn, $listOrder); ?>
							</th>

							<th class='left'>
							<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ORDERS_CDATE', 'a.cdate', $listDirn, $listOrder); ?>
							</th>

							<th class='left'>
							<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ORDERS_ORIGINAL_AMOUNT', 'a.original_amount', $listDirn, $listOrder); ?>
							</th>

							<th class='left'>
							<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ORDERS_COUPON_DISCOUNT', 'a.coupon_discount', $listDirn, $listOrder); ?>
							</th>

							<th class='left'>
							<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ORDERS_AMOUNT', 'a.amount', $listDirn, $listOrder); ?>
							</th>

							<th class='left'>
							<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ORDERS_STATUS', 'a.status', $listDirn, $listOrder); ?>
							</th>

							<th class='left'>
							<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ORDERS_PROCESSOR', 'a.processor', $listDirn, $listOrder); ?>
							</th>

							<th class='left'>
							<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ORDERS_ORDER_TAX', 'a.order_tax', $listDirn, $listOrder); ?>
							</th>

						<?php if (isset($this->items[0]->id)): ?>
							<th width="1%" class="nowrap center hidden-phone hidden-xs hidden-sm">
								<?php echo HTMLHelper::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
							</th>
						<?php endif; ?>
						</tr>
					</thead>
					<tfoot>
						<?php
							if (isset($this->items[0]))
							{
								$colspan = count(get_object_vars($this->items[0]));
							}
							else
							{
								$colspan = 12;
							}
						?>
						<tr>
							<td colspan="<?php echo $colspan ?>">
								<div class="pager">
									<?php echo $this->pagination->getPagesLinks(); ?>
								</div>
							</td>
						</tr>
					</tfoot>
					<tbody>
					<?php foreach ($this->items as $i => $item) :

						$ordering   = ($listOrder == 'a.ordering');

						$link_for_orders = $this->comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=orders&layout=order&orderid='.$item->order_id.'&tmpl=component', false);
						?>
						<tr class="row<?php echo $i % 2; ?>">

						<td>
							<a rel="{handler: 'iframe', size: {x: 900, y: 600}}" class="tjmodal tjlmsNoModal" href="<?php echo $link_for_orders;?>"><?php if($item->order_id) echo $item->order_id; else echo $item->id;?></a>
						</td>
						<td>
							<a href="<?php echo $this->comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id='.$item->course_id); ?>"><?php echo $item->courseName; ?></a>
						</td>
						<td><?php echo $item->local_cdate;	?></td>
						<td><?php echo $item->original_amount; ?></td>
						<td><?php echo $item->coupon_discount; ?></td>
						<td><?php echo $item->amount; ?></td>
						<td>
							<?php
							$processor="'".$item->processor."'";
									if(($item->status) AND (!empty($item->processor)))
									{
										switch ($item->status)
										{
											case 'C':
														echo Text::_('COM_TJLMS_PSTATUS_COMPLETED');
														break;
											case 'P':
														echo Text::_('COM_TJLMS_PSTATUS_PENDING');
														break;
											case 'D':
														echo Text::_('COM_TJLMS_PSTATUS_DECLINED');
														break;
											case 'E':
														echo Text::_('COM_TJLMS_PSTATUS_FAILED');
														break;
											case 'UR':
														echo Text::_('COM_TJLMS_PSTATUS_UNDERREVIW');
														break;
											case 'RF':
														echo Text::_('COM_TJLMS_PSTATUS_REFUNDED');
														break;
											case 'CRV':
														echo Text::_('COM_TJLMS_PSTATUS_CANCEL_REVERSED');
														break;
											case 'RV':
														echo Text::_('COM_TJLMS_PSTATUS_REVERSED');
														break;
										}
									}
									else
									echo $this->paymentStatus[$data->status];
								?>
						</td>
						<td><?php echo $item->processor; ?></td>
						<td><?php echo $item->order_tax; ?></td>

						<?php if (isset($this->items[0]->id)): ?>
							<td class="center hidden-xs hidden-sm">
								<?php echo (int) $item->id; ?>
							</td>
						<?php endif; ?>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php endif;?>
			<input type="hidden" id='order_id' name="order_id" value="" />
			<input type="hidden" id='payment_status' name="payment_status" value="" />
			<input type="hidden" id='processor' name="processor" value="" />
			<input type="hidden" id='controller' name="controller" value="orders" />
			<input type="hidden" id='task' name="task" value="" />
			<input type="hidden" id='option' name="option" value="com_tjlms" />
			<input type="hidden" name="boxchecked" value="0" />
			<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
			<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	</div>
</div>
