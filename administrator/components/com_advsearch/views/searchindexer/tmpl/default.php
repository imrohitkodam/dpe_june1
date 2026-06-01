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

JHtml::_('behavior.tooltip');
JHTML::_('script','system/multiselect.js',false,true);
// Import CSS
$document = JFactory::getDocument();
$document->addStyleSheet('components/com_advsearch/assets/css/advsearch.css');
require_once JPATH_COMPONENT.'/helpers/advsearch.php';
$user	= JFactory::getUser();
$userId	= $user->get('id');
$listOrder	= $this->state->get('list.ordering');
$listDirn	= $this->state->get('list.direction');
$canOrder	= $user->authorise('core.edit.state', 'com_advsearch');
$saveOrder	= $listOrder == 'a.ordering';
$db = JFactory::getDBO();
?>

<form action="<?php echo JRoute::_('index.php?option=com_advsearch&view=searchindexer'); ?>" method="post" name="adminForm" id="adminForm">
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
				<?php echo JHtml::_('grid.sort',  'COM_ADVSEARCH_SEARCHINDEXER_CONTENT_TYPE', 'a.content_type', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo JHtml::_('grid.sort',  'COM_ADVSEARCH_SEARCHINDEXER_CLIENT', 'a.client', $listDirn, $listOrder); ?>
				</th>
				<!--th class='left'>
				<?php echo JHtml::_('grid.sort',  'COM_ADVSEARCH_SEARCHINDEXER_FIELD_TYPE', 'a.field_type', $listDirn, $listOrder); ?>
				</th-->
				<th class='left'>
				<?php echo JHtml::_('grid.sort',  'COM_ADVSEARCH_SEARCHINDEXER_MAP_TABLE', 'a.map_table', $listDirn, $listOrder); ?>
				</th>
				<!--th class='left'>
				<?php echo JHtml::_('grid.sort',  'COM_ADVSEARCH_SEARCHINDEXER_MAPPING_FIELD', 'a.mapping_field', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo JHtml::_('grid.sort',  'COM_ADVSEARCH_SEARCHINDEXER_MAPPING_LABEL', 'a.mapping_label', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo JHtml::_('grid.sort',  'COM_ADVSEARCH_SEARCHINDEXER_OPTIONS', 'a.options', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo JHtml::_('grid.sort',  'COM_ADVSEARCH_SEARCHINDEXER_CATEGORY', 'a.category', $listDirn, $listOrder); ?>
				</th-->
				<th class='left'>
				<?php echo JHtml::_('grid.sort',  'Last Cron Date', 'a.field_code', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo JHtml::_('grid.sort',  'Recourds Count', 'a.field_code', $listDirn, $listOrder); ?>
				</th>

                <?php /*if (isset($this->items[0]->state)) { ?>
				<th width="5%">
					<?php echo JHtml::_('grid.sort',  'JPUBLISHED', 'a.state', $listDirn, $listOrder); ?>
				</th>
                <?php }*/ ?>
                <?php if (isset($this->items[0]->ordering)) { ?>
				<!--th width="10%">
					<?php echo JHtml::_('grid.sort',  'JGRID_HEADING_ORDERING', 'a.ordering', $listDirn, $listOrder); ?>
					<?php if ($canOrder && $saveOrder) :?>
						<?php echo JHtml::_('grid.order',  $this->items, 'filesave.png', 'searchindexer.saveorder'); ?>
					<?php endif; ?>
				</th-->
                <?php }
                ?>

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
					<?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'searchindexer.', $canCheckin); ?>
				<?php endif; ?>
					<a href="<?php echo JRoute::_('index.php?option=com_advsearch&view=createindexer&layout=edit&id='.(int) $item->id); ?>">
				<?php if ($canEdit) : ?>
					<?php echo $item->name; ?>

				</td>
				<td>
				<?php echo $this->escape($item->client); ?></a>
				<?php else : ?>
					<?php echo $this->escape($item->client); ?>
				<?php endif; ?>
				</td>

				<!--td>
					<?php echo $item->field_type; ?>
				</td-->
				<td>
					<?php echo '#__'.$item->mapped_table; ?>
				</td>
				<!--td>
					<?php echo $item->mapping_field; ?>
				</td>
				<td>
					<?php echo $item->mapping_label; ?>
				</td-->
				<!--td>
					<?php echo $item->options; ?>
				</td>
				<td>
					<?php echo $item->category; ?>
				</td-->
				<td>
					<?php


					/*$query = "SELECT end_date FROM  #__advanced_search_cronjob WHERE type = '$item->type_name' ORDER BY end_date DESC";
					$db->setQuery($query);
					$end_date = $db->loadResult();*/
					$end_date = '';

					if($end_date)
						echo $end_date;
					else
						echo 'Not yet executed'; ?>
				</td>

				<td>
					<?php


					/*$query = "SELECT count(id) FROM  ".$db->quoteName('#__advanced_search_zoo_'.$item->type_name);
					$db->setQuery($query);
					$count = $db->loadResult();*/
					$count = '';

					if($count)
						echo $count;
					else
						echo 'NA'; ?>
				</td>


                <?php if (isset($this->items[0]->state)) { ?>
				    <!--td class="center">
					    <?php echo JHtml::_('jgrid.published', $item->state, $i, 'searchindexer.', $canChange, 'cb'); ?>
				    </td-->
                <?php } ?>
                <?php if (isset($this->items[0]->ordering)) { ?>
				    <!--td class="order">
					    <?php if ($canChange) : ?>
						    <?php if ($saveOrder) :?>
							    <?php if ($listDirn == 'asc') : ?>
								    <span><?php echo $this->pagination->orderUpIcon($i, true, 'searchindexer.orderup', 'JLIB_HTML_MOVE_UP', $ordering); ?></span>
								    <span><?php echo $this->pagination->orderDownIcon($i, $this->pagination->total, true, 'searchindexer.orderdown', 'JLIB_HTML_MOVE_DOWN', $ordering); ?></span>
							    <?php elseif ($listDirn == 'desc') : ?>
								    <span><?php echo $this->pagination->orderUpIcon($i, true, 'searchindexer.orderdown', 'JLIB_HTML_MOVE_UP', $ordering); ?></span>
								    <span><?php echo $this->pagination->orderDownIcon($i, $this->pagination->total, true, 'searchindexer.orderup', 'JLIB_HTML_MOVE_DOWN', $ordering); ?></span>
							    <?php endif; ?>
						    <?php endif; ?>
						    <?php $disabled = $saveOrder ?  '' : 'disabled="disabled"'; ?>
						    <input type="text" name="order[]" size="5" value="<?php echo $item->ordering;?>" <?php echo $disabled ?> class="text-area-order" />
					    <?php else : ?>
						    <?php echo $item->ordering; ?>
					    <?php endif; ?>
				    </td-->
                <?php } ?>
                <?php if (isset($this->items[0]->id)) { ?>
				<!--td class="center">
					<?php echo (int) $item->id; ?>
				</td-->
                <?php } ?>
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

<?php
$AdvsearchHelper  = new AdvsearchHelper;

echo $AdvsearchHelper->getFooterNote(); ?>
