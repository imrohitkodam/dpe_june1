<?php
/**
 * @version     1.0.0
 * @package     com_advsearch
 * @copyright   Copyright (C) 2012. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      amol <amol_p@tekdi.net> - http://tekdi.net
 */


// no direct access
defined('_JEXEC') or die;

$AdvsearchHelper  = new AdvsearchHelper;

//~ echo "<pre>";
//~ print_r($this->items);
//~ echo "</pre>";
//~ die;
JHtml::_('bootstrap.tooltip');
JHTML::_('script','system/multiselect.js',false,true);
// Import CSS
$document = JFactory::getDocument();
$document->addStyleSheet('components/com_advsearch/assets/css/advsearch.css');
JLoader::import('AdvsearchHelper', JPATH_ROOT . '/components/com_advsearch/helpers/advsearch.php');
$user	= JFactory::getUser();
$userId	= $user->get('id');
$listOrder	= $this->state->get('list.ordering');
$listDirn	= $this->state->get('list.direction');
$canOrder	= $user->authorise('core.edit.state', 'com_advsearch');
$saveOrder	= $listOrder == 'a.ordering';
$db = JFactory::getDBO();
?>

<form action="<?php echo JRoute::_('index.php?option=com_advsearch&view=mapping'); ?>" method="post" name="adminForm" id="adminForm">
	<fieldset id="filter-bar">
		<div class="filter-search fltlft">
			<label class="filter-search-lbl" for="filter_search"><?php echo JText::_('JSEARCH_FILTER_LABEL'); ?></label>
			<input type="text" name="filter_search" id="filter_search" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo JText::_('Search'); ?>" />
			<button type="submit"><?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?></button>
			<button type="button" onclick="document.id('filter_search').value='';this.form.submit();"><?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?></button>
		</div>
	</fieldset>
	<div class="clr"> </div>

	<table class="adminlist" style="margin-top:35px;">
		<thead>
			<tr>
				<th width="1%">
					<input type="checkbox" name="checkall-toggle" value="" onclick="checkAll(this)" />
				</th>
				<th class='left'>
				<?php echo JHtml::_('grid.sort',  'COM_ADVSEARCH_MAPPING_NAME', 'a.mapping_name', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo JHtml::_('grid.sort',  'COM_ADVSEARCH_MAPPING_PRIMARY_INDEX_1', 'a.primary_index_id', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo JHtml::_('grid.sort',  'COM_ADVSEARCH_MAPPING_PRIMARY_INDEX_2', 'a.secondary_index_id', $listDirn, $listOrder); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="10">
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
		<?php foreach ($this->items as $i => $item) :
			$ordering	= ($listOrder == 'a.ordering');
			$canCreate	= $user->authorise('core.create',		'com_advsearch');
			$canEdit	= $user->authorise('core.edit',			'com_advsearch');
			$canCheckin	= $user->authorise('core.manage',		'com_advsearch');
			$canChange	= $user->authorise('core.edit.state',	'com_advsearch');
			?>
			<tr class="row<?php echo $i % 2; ?>">
				<td class="center">
					<?php echo JHtml::_('grid.id', $i, $item->id); ?>
				</td>

				<td>
				<?php if (isset($item->checked_out) && $item->checked_out) : ?>
					<?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'mapping.', $canCheckin); ?>
				<?php endif; ?>
					<a href="<?php echo JRoute::_('index.php?option=com_advsearch&view=createmapping&id='.(int) $item->primary_index_id); ?>">
					<?php echo $item->mapping_name; ?></a>

				</td>

				<td>
					<?php
						if ($item->primary_index_id)
						{
							$whereCondition = 'id = ' . $item->primary_index_id;
							echo $primary_IndexerName = $AdvsearchHelper->getDataValues('#__advanced_search_indexer', 'name', $whereCondition, 'loadResult');
						} ?>
				</td>
				<td>
					<?php
						if ($item->secondary_index_id)
						{
							$whereCondition = 'id = ' . $item->secondary_index_id;
							echo $secondary_IndexerName = $AdvsearchHelper->getDataValues('#__advanced_search_indexer', 'name', $whereCondition, 'loadResult');
						} ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<div>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>

<?php echo $AdvsearchHelper->getFooterNote(); ?>
