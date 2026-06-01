_<?php
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
jimport('techjoomla.common');

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

HTMLHelper::addIncludePath(JPATH_COMPONENT.'/helpers/html');

$this->techjoomlacommon = new TechjoomlaCommon;
$lmsparams              = ComponentHelper::getParams('com_tjlms');
$show_user_or_username  = $lmsparams->get('show_user_or_username', 'name');
$date_format_show       = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

if (JVERSION >= '3.0')
{
	HTMLHelper::_('bootstrap.tooltip');
	HTMLHelper::_('behavior.multiselect');
	HTMLHelper::_('formbehavior.chosen', 'select');
}

HTMLHelper::_('behavior.modal', 'a.modal');
// Import CSS
$document  = Factory::getDocument();
$user      = Factory::getUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canOrder  = $user->authorise('core.edit.state', 'com_tjlms');
$saveOrder = $listOrder == 'a.ordering';

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_tjlms&task=orders.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'orderList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

$orderModel       = BaseDatabaseModel::getInstance('Orders', 'TjlmsModel');
$payment_statuses = $orderModel->getPaymentStatusFilter();

$js_key ="
function selectstatusorder(appid,processor,ele)
{
	document.getElementById('order_id').value = appid;
	document.getElementById('payment_status').value = ele;
	document.getElementById('processor').value = processor;
	document.getElementById('task').value = 'save';
	";

	if(JVERSION >='1.6.0')
	{

	$js_key.="
	Joomla.submitform('orders.save', document.getElementById('adminForm'));";
	}
	else
	{
	$js_key.="
	document.adminForm.submit();";
	}

	$js_key.="}";
	$document->addScriptDeclaration($js_key);

?>
<script type="text/javascript">
	jQuery( document ).ready(function() {
		jQuery('#payment_statuses0_chzn').removeAttr('width');
	});
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

<form action="<?php echo Route::_('index.php?option=com_tjlms&view=orders'); ?>" method="post" name="adminForm" id="adminForm">
	<?php
		ob_start();
		include JPATH_BASE . '/components/com_tjlms/layouts/header.sidebar.php';
		$layoutOutput = ob_get_contents();
		ob_end_clean();
		echo $layoutOutput;

		echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
	?>

	<div class="clearfix"> </div>
	<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
		<div>
		<table class="table table-striped tjlms-order-table" id="orderList">
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


				<th class='left'>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ORDERS_ORDER_ID', 'a.id', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ORDERS_COURSE_ID', 'a.course_id', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ORDERS_NAME', 'a.name', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ORDERS_EMAIL', 'a.email', $listDirn, $listOrder); ?>
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

				</tr>
			</thead>
			<tfoot>
				<?php
				if(isset($this->items[0])){
					$colspan = count(get_object_vars($this->items[0]));
				}
				else{
					$colspan = 12;
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

				$ordering        = ($listOrder == 'a.ordering');
				$canCreate       = $user->authorise('core.create',		'com_tjlms');
				$canEdit         = $user->authorise('core.edit',			'com_tjlms');
				$canCheckin      = $user->authorise('core.manage',		'com_tjlms');
				$canChange       = $user->authorise('core.edit.state',	'com_tjlms');
				$link_for_orders = Route::_('index.php?option=com_tjlms&view=orders&layout=order&orderid='.$item->order_id.'&tmpl=component');
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


				<td>
				<?php if (isset($item->checked_out) && $item->checked_out) : ?>
					<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'orders.', $canCheckin); ?>
				<?php endif; ?>

					<a class="modal" onclick=opentjlmsSqueezeBox("<?php echo $link_for_orders;?>")><?php if($item->order_id) echo $item->order_id; else echo $item->id;?></a>
				</td>
				<td class="break-word">

					<?php echo $item->courseName; ?>
				</td>
				<td>

					<?php echo $userName = ($show_user_or_username == 'user' ? $item->name : $item->user_id ); ?>
				</td>
				<td class="break-word">

					<?php echo $item->email; ?>
				</td>

				<td>

					<?php echo $this->techjoomlacommon->getDateInLocal($item->cdate,0,$date_format_show);?>
				</td>

				<td>
					<?php echo $item->original_amount; ?>
				</td>
				<td>

					<?php echo $item->coupon_discount; ?>
				</td>
				<td>

					<?php echo $item->amount; ?>
				</td>

				<td width="125px;">
					<?php
					$processor="'".$item->processor."'";
							if(($item->status) AND (!empty($item->processor)))
							{
								echo HTMLHelper::_('select.genericlist',$payment_statuses,"payment_statuses".$i,'class="pad_status span30 input-lg" id="pad_status" size="1" onChange="selectstatusorder('.$item->id.','.$processor.',this.value);"',"value","text",$item->status);
							}
							else
							echo $payment_statuses[$data->status];
						?>
				</td>
				<td>

					<?php echo $item->processor; ?>
				</td>


				<td>

					<?php echo $item->order_tax; ?>
				</td>


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
		</div>
	</div>
</form>
</div>


