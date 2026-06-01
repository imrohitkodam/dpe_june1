<?php
/**
* @version 2.0.0
* @package RSTickets! Pro 2.0.0
* @copyright (C) 2010 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('_JEXEC') or die('Restricted access');

$listOrder 	= $this->escape($this->state->get('list.ordering'));
$listDirn 	= $this->escape($this->state->get('list.direction')); 
$saveOrder	= $listOrder == 'ordering';
$ordering	= $listOrder == 'ordering';
if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_rsticketspro&task=crons.saveOrderAjax&tmpl=component';
	JHtml::_('sortablelist.sortable', 'articleList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
?>
<form action="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=crons'); ?>" method="post" name="adminForm" id="adminForm">
	<?php
	echo RsticketsproAdapterGrid::sidebar();

	echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));

	if (empty($this->items))
	{
		?>
		<div class="alert alert-info">
			<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?php echo JText::_('INFO'); ?></span>
			<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
		<?php
	}
	else
	{
		?>
		<table class="table table-striped" id="articleList">
			<thead>
				<tr>
					<th width="1%" nowrap="nowrap"><?php echo JHtml::_('grid.checkall'); ?></th>
					<th style="width:1%" class="nowrap text-center"><?php echo JHtml::_('searchtools.sort', '', 'ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
					<th><?php echo JHtml::_('searchtools.sort', 'RST_ACCOUNT_NAME', 'name', $listDirn, $listOrder); ?></th>
					<th><?php echo JHtml::_('searchtools.sort', 'RST_ACCOUNT_SERVER', 'server', $listDirn, $listOrder); ?></th>
					<th width="1%" nowrap="nowrap"><?php echo JHtml::_('searchtools.sort', 'JPUBLISHED', 'published', $listDirn, $listOrder); ?></th>
					<th width="1%"><?php echo JHtml::_('searchtools.sort', 'ID', 'id', $listDirn, $listOrder); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ($this->items as $i => $item)
				{
					?>
					<tr>
						<td width="1%" nowrap="nowrap" class="center" align="center"><?php echo JHtml::_('grid.id', $i, $item->id); ?></td>
						<td class="order center">
							<?php
							$disableClassName = '';
							$disabledLabel	  = '';

							if (!$saveOrder)
							{
								$disabledLabel    = JText::_('JORDERINGDISABLED');
								$disableClassName = 'inactive';
							}
							?>
							<span class="sortable-handler <?php echo $disableClassName; ?>" title="<?php echo $disabledLabel; ?>">
								<i class="icon-menu"></i>
							</span>
							<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order" />
						</td>
						<td>
							<button type="button" onclick="jQuery('#rsticketsproCronModal<?php echo $item->id; ?>').modal('show');" class="btn btn-secondary btn-small btn-sm"><?php echo JText::_('RST_ACCOUNT_TEST_CONNECTION'); ?></button>

							<a href="<?php echo JRoute::_('index.php?option=com_rsticketspro&task=cron.edit&id='.$item->id); ?>"><?php echo $item->name != '' ? $this->escape($item->name) : JText::_('RST_NO_TITLE'); ?></a>
							<?php
							echo JHtml::_('bootstrap.renderModal', 'rsticketsproCronModal' . $item->id, array(
								'title' => JText::_('RST_ACCOUNT_TEST_CONNECTION'),
								'url' 	=> JRoute::_('index.php?option=com_rsticketspro&task=cron.preview&tmpl=component&id=' . $item->id, false),
								'height' => 400,
								'backdrop' => 'static'));
							?>
						</td>
						<td><?php echo $this->escape($item->server . ':' . $item->port); ?></td>
						<td width="1%" nowrap="nowrap" class="center" align="center"><?php echo JHtml::_('jgrid.published', $item->published, $i, 'crons.'); ?></td>
						<td width="1%"><?php echo $this->escape($item->id); ?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<?php
		echo $this->pagination->getListFooter();
	}
	?>
		<div>
			<?php echo JHtml::_( 'form.token' ); ?>
			<input type="hidden" name="boxchecked" value="0" />
			<input type="hidden" name="task" value="" />
		</div>
	</div>
</form>