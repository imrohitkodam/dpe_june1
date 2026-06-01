<?php
/**
 * @package     TJ-Fields
 * @subpackage  com_tjfields
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
Factory::getApplication()->getDocument()->getWebAssetManager()->useStyle('searchtools')->useScript('searchtools');

// Import CSS
$document = Factory::getDocument();
$document->addStyleSheet('components/com_tjfields/assets/css/tjfields.css');

$user	= Factory::getUser();
$userId	= $user->get('id');
$listOrder	= $this->state->get('list.ordering');
$listDirn	= $this->state->get('list.direction');
$canOrder	= $user->authorise('core.edit.state', 'com_tjfields');
$saveOrder	= $listOrder == 'a.ordering';
$input = Factory::getApplication()->input;

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_tjfields&task=groups.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'groupList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
$sortFields = $this->getSortFields();
?>
<script type="text/javascript">
	Joomla.submitbutton = function (task) {
		if (task == 'groups.delete')
		{
			if(confirm("<?php echo Text::_('COM_TJFIELDS_GROUPS_DELETE_CONFIRMATION'); ?>"))
			{
				Joomla.submitform(task);
			}
			else
			{
				return false;
			}
		}
		else
		{
			Joomla.submitform(task);
		}
	}

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

<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
</div>
<?php // DPE hack To show navigation link of field groups and condition as per there ucm type 
$category_url    = Route::_('index.php?option=com_categories&extension=' . $input->get('client','','STRING'));
$field_group_url = Route::_('index.php?option=com_tjfields&view=groups&client=' . $input->get('client','','STRING'));
$fields_url      = Route::_('index.php?option=com_tjfields&view=fields&client=' . $input->get('client','','STRING') . '&extension=' . $input->get('client','','STRING'));
$conditionsUrl   = Route::_('index.php?option=com_tjfields&view=conditions&client=' . $input->get('client','','STRING'));

?>
<div class="float-end">
	<a href="<?php echo $field_group_url;?>" class="btn btn-success"><i class="fa fa-link" aria-hidden="true"></i>&nbsp<?php echo Text::_('COM_TJUCM_TYPES_FIELD_GROUP_URL');?></a>
<a href="<?php echo $fields_url;?>" class="btn btn-success"><i class="fa fa-link" aria-hidden="true"></i>&nbsp <?php echo Text::_('COM_TJUCM_TYPES_FIELDS_URL');?></a>
<a href="<?php echo $conditionsUrl;?>" class="btn btn-success"><i class="fa fa-link" aria-hidden="true"></i>&nbsp <?php echo Text::_('COM_TJUCM_TYPES_CONDITIONS_URL');?></a>
<br>
</div>
<br>
<br>
<br>
<form action="<?php echo Route::_('index.php?option=com_tjfields&view=groups&client='.$input->get('client','','STRING')); ?>" method="post" name="adminForm" id="adminForm">
	<div class="j-main-container" id="j-main-container">
			<div class="tjBs3 col-md-12">
				<div class="js-stools-container-selector filter-search btn-group pull-left">
					<input type="text" name="filter_search" id="filter_search"
					placeholder="<?php echo Text::_('COM_TJFIELDS_FILTER_SEARCH_DESC_COUNTRIES'); ?>"
					value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
					class="hasTooltip form-control"
					title="<?php echo Text::_('COM_TJFIELDS_FILTER_SEARCH_DESC_COUNTRIES'); ?>" />

					<button type="submit" class="btn btn-primary hasTooltip"
					title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>">
						<i class="icon-search"></i>
					</button>
					<button type="button" class="btn btn-primary hasTooltip"
					title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"
					onclick="document.getElementById('filter_search').value='';this.form.submit();">
						<i class="icon-remove"></i>
					</button>
				</div>

				<div class="js-stools-container-selector btn-group pull-right hidden-phone">
					<?php
					echo HTMLHelper::_('select.genericlist', $this->publish_states, "filter_published", 'class="form-select" size="1" onchange="document.adminForm.submit();" name="filter_published"', "value", "text", $this->state->get('filter.state'));
					?>
				</div>
				<?php if(JVERSION >= '3.0'):?>
				<div class="js-stools-container-selector btn-group pull-right hidden-phone">
					<label for="limit" class="element-invisible"><?php echo Text::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC');?></label>
					<?php echo $this->pagination->getLimitBox(); ?>
				</div>

				<div class="js-stools-container-selector btn-group pull-right hidden-phone">
					<label for="directionTable" class="element-invisible"><?php echo Text::_('JFIELD_ORDERING_DESC');?></label>
					<select name="directionTable" id="directionTable" class="form-select" onchange="Joomla.orderTable()">
						<option value=""><?php echo Text::_('JFIELD_ORDERING_DESC');?></option>
						<option value="asc" <?php if ($listDirn == 'asc') echo 'selected="selected"'; ?>><?php echo Text::_('JGLOBAL_ORDER_ASCENDING');?></option>
						<option value="desc" <?php if ($listDirn == 'desc') echo 'selected="selected"'; ?>><?php echo Text::_('JGLOBAL_ORDER_DESCENDING');?></option>
					</select>
				</div>

				<div class="btn-group pull-right">
					<label for="sortTable" class="element-invisible"><?php echo Text::_('JGLOBAL_SORT_BY');?></label>
						<select name="sortTable" id="sortTable" class="form-select" onchange="Joomla.orderTable()">
							<option value=""><?php echo Text::_('JGLOBAL_SORT_BY');?></option>
						<?php echo HTMLHelper::_('select.options', $sortFields, 'value', 'text', $listOrder);?>
						</select>
				</div>
					<?php endif;?>
			</div>
			<?php
			if (empty($this->items))
			{
			?>
				<div class="alert alert-no-items">
					<?php echo Text::_('COM_TJFIELD_NO_GROUP_FOUND');?>
				</div>
				<?php
			}
			else
			{?>
			<div>&nbsp;</div>
			<table class="table" id="groupList">
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
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJFIELDS_GROUPS_CREATED_BY', 'a.created_by_name', $listDirn, $listOrder); ?>
					</th>
					<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJFIELDS_GROUPS_NAME', 'a.name', $listDirn, $listOrder); ?>
					</th>
					<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJFIELDS_GROUPS_CLIENT', 'a.client', $listDirn, $listOrder); ?>
					</th>
					<?php if (isset($this->items[0]->id)): ?>
						<th width="1%" class="nowrap center hidden-phone">
							<?php echo HTMLHelper::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
						</th>
					<?php endif; ?>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($this->items as $i => $item) :
					$ordering   = ($listOrder == 'a.ordering');
					$canCreate	= $user->authorise('core.create',		'com_tjfields');
					$canEdit	= $user->authorise('core.edit',			'com_tjfields');
					$canCheckin	= $user->authorise('core.manage',		'com_tjfields');
					$canChange	= $user->authorise('core.edit.state',	'com_tjfields');
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
							<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'groups.', $canChange, 'cb'); ?>
						</td>
					<?php endif; ?>

					<td>
						<?php echo $item->created_by_name; ?>
					</td>
					<td>
					<?php if (isset($item->checked_out) && $item->checked_out) : ?>
						<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'groups.', $canCheckin); ?>
					<?php endif; ?>
					<?php if ($canEdit) : ?>
						<a href="<?php echo Route::_('index.php?option=com_tjfields&task=group.edit&client='.$item->client.'&id='.(int) $item->id); ?>">
						<?php echo $this->escape($item->name); ?></a>
					<?php else : ?>
						<?php echo $this->escape($item->name); ?>
					<?php endif; ?>
					</td>
					<td>
						<?php echo $item->client; ?>
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
			<?php echo $this->pagination->getListFooter(); ?>
			<?php
			}?>
			<input type="hidden" name="task" value="" />
			<input type="hidden" name="boxchecked" value="0" />
			<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
			<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
			<?php echo HTMLHelper::_('form.token'); ?>
		</div>
	</form>
</div>
