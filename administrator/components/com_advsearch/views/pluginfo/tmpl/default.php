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
?>

<!--form action="<?php echo JRoute::_('index.php?option=com_advsearch&view=searchindexer'); ?>" method="post" name="adminForm" id="adminForm">
	<fieldset id="filter-bar">
		<div class="filter-search fltlft">
			<label class="filter-search-lbl" for="filter_search"><?php echo JText::_('JSEARCH_FILTER_LABEL'); ?></label>
			<input type="text" name="filter_search" id="filter_search" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo JText::_('Search'); ?>" />
			<button type="submit"><?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?></button>
			<button type="button" onclick="document.id('filter_search').value='';this.form.submit();"><?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?></button>
		</div>
		
        

	</fieldset>
	<div class="clr"> </div>

	<table class="adminlist">
		<thead>
			<tr>
				<th width="1%">
					<input type="checkbox" name="checkall-toggle" value="" onclick="checkAll(this)" />
				</th>

				<th class='left'>
				<?php echo JHtml::_('grid.sort',  'COM_ADVSEARCH_SEARCHINDEXER_CLIENT', 'a.client', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo JHtml::_('grid.sort',  'COM_ADVSEARCH_SEARCHINDEXER_CONTENT_TYPE', 'a.content_type', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo JHtml::_('grid.sort',  'COM_ADVSEARCH_SEARCHINDEXER_FIELD_CODE', 'a.field_code', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo JHtml::_('grid.sort',  'COM_ADVSEARCH_SEARCHINDEXER_FIELD_TYPE', 'a.field_type', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo JHtml::_('grid.sort',  'COM_ADVSEARCH_SEARCHINDEXER_MAP_TABLE', 'a.map_table', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
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
				</th>


                <?php if (isset($this->items[0]->state)) { ?>
				<th width="5%">
					<?php echo JHtml::_('grid.sort',  'JPUBLISHED', 'a.state', $listDirn, $listOrder); ?>
				</th>
                <?php } ?>
                <?php if (isset($this->items[0]->ordering)) { ?>
				<th width="10%">
					<?php echo JHtml::_('grid.sort',  'JGRID_HEADING_ORDERING', 'a.ordering', $listDirn, $listOrder); ?>
					<?php if ($canOrder && $saveOrder) :?>
						<?php echo JHtml::_('grid.order',  $this->items, 'filesave.png', 'searchindexer.saveorder'); ?>
					<?php endif; ?>
				</th>
                <?php } ?>
                <?php if (isset($this->items[0]->id)) { ?>
                <th width="1%" class="nowrap">
                    <?php echo JHtml::_('grid.sort',  'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                </th>
                <?php } ?>
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
				<?php if ($canEdit) : ?>
					<a href="<?php echo JRoute::_('index.php?option=com_advsearch&task=createsearchindexer.edit&id='.(int) $item->id); ?>">
					<?php echo $this->escape($item->client); ?></a>
				<?php else : ?>
					<?php echo $this->escape($item->client); ?>
				<?php endif; ?>
				</td>
				<td>
					<?php echo $item->content_type; ?>
				</td>
				<td>
					<?php echo $item->field_code; ?>
				</td>
				<td>
					<?php echo $item->field_type; ?>
				</td>
				<td>
					<?php echo $item->map_table; ?>
				</td>
				<td>
					<?php echo $item->mapping_field; ?>
				</td>
				<td>
					<?php echo $item->mapping_label; ?>
				</td>
				<td>
					<?php echo $item->options; ?>
				</td>
				<td>
					<?php echo $item->category; ?>
				</td>


                <?php if (isset($this->items[0]->state)) { ?>
				    <td class="center">
					    <?php echo JHtml::_('jgrid.published', $item->state, $i, 'searchindexer.', $canChange, 'cb'); ?>
				    </td>
                <?php } ?>
                <?php if (isset($this->items[0]->ordering)) { ?>
				    <td class="order">
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
				    </td>
                <?php } ?>
                <?php if (isset($this->items[0]->id)) { ?>
				<td class="center">
					<?php echo (int) $item->id; ?>
				</td>
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
</form-->


<fieldset class="adminform">
	<legend>Stat Details</legend>
	<div class="m">
	    <table border="0" width="100%" class="adminlist">
		<tbody>
			<tr> </tr>	
			<tr> </tr>	
			<tr id="row1" class="row0">
				<td align="left" width="10%"><strong>No.</strong></td>
				<td align="left" width="20%"><strong>Plugin Name</strong></td>
				<td align="left" width="5%"><strong>Status</strong></td>
				<td align="left" width="40%"><strong>Information</strong></td>
			</tr>	
			<tr id="row1" class="row1">
				<td align="left"><strong>1</strong></td>
				<td align="left"><strong>Advanced Search Zoo Plugin</strong></td>
				<td>&nbsp;</td>
				<td align="left"><strong>This plugin works with Zoo extension.</strong></td>
			</tr>			
		    <tr id="row1" class="row0">
				<td align="left"><strong>2</strong></td>
				<td align="left"><strong>Advanced Search Jom Social Plugin</strong></td>
				<td>&nbsp;</td>
				<td align="left"><strong>This plugin works with Jomsocial extension.</strong></td>
			</tr>			
			<tr id="row1" class="row1">
				<td align="left"><strong>3</strong></td>
				<td align="left"><strong>Advanced Search CB Plugin</strong></td>
				<td>&nbsp;</td>
				<td align="left"><strong>This plugin works with CB extension.</strong></td>
			</tr>	
			</tbody></table></div>
</fieldset>

<?php echo AdvsearchHelper::getFooterNote(); ?>
