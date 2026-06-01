<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

JFactory::getApplication()->enqueueMessage(JText::_('RST_DEPARTMENT_TRANSLATE'));

$canEdit  	= JFactory::getUser()->authorise('department.edit', 'com_rsticketspro');
$canChange  = JFactory::getUser()->authorise('department.edit.state', 'com_rsticketspro');
$listOrder 	= $this->escape($this->state->get('list.ordering'));
$listDirn 	= $this->escape($this->state->get('list.direction'));
$saveOrder	= $listOrder == 'ordering' && $canChange;

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_rsticketspro&task=departments.saveOrderAjax&tmpl=component';
	JHtml::_('sortablelist.sortable', 'articleList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
?>
<form action="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=departments'); ?>" method="post" name="adminForm" id="adminForm">
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
					<th style="width:1%" class="text-center"><?php echo JHtml::_('grid.checkall'); ?></th>
					<th style="width:1%" class="nowrap text-center"><?php echo JHtml::_('searchtools.sort', '', 'ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?></th>
					<th></th>
					<th><?php echo JHtml::_('searchtools.sort', 'RST_DEPARTMENT', 'name', $listDirn, $listOrder); ?></th>
					<th><?php echo JHtml::_('searchtools.sort', 'RST_PREFIX', 'prefix', $listDirn, $listOrder); ?></th>
					<th width="1%" nowrap="nowrap"><?php echo JHtml::_('searchtools.sort', 'JPUBLISHED', 'published', $listDirn, $listOrder); ?></th>
					<th width="1%"><?php echo JHtml::_('searchtools.sort', 'ID', 'id', $listDirn, $listOrder); ?></th>
				</tr>
			</thead>
			<tbody <?php if ($saveOrder) { ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="false"<?php } ?>>
			<?php
			foreach ($this->items as $i => $item)
			{
				?>
				<tr data-draggable-group="1">
					<td width="1%" nowrap="nowrap"><?php echo JHtml::_('grid.id', $i, $item->id); ?></td>
					<td class="order text-center">
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
					<td width="1%" nowrap="nowrap">
						<a class="btn btn-secondary btn-small btn-sm" href="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=customfields&filter_department_id='.(int) $item->id); ?>"><small>[<?php echo JText::_('RST_CUSTOM_FIELDS'); ?>]</small></a>
					</td>
					<td>
						<?php
						if ($canEdit)
						{
							echo JHtml::_('link', JRoute::_('index.php?option=com_rsticketspro&task=department.edit&id='.(int) $item->id), $this->escape($item->name));
						}
						else
						{
							echo $this->escape($item->name);
						}
						?>
					</td>
					<td><?php echo $this->escape($item->prefix); ?></td>
					<td width="1%" nowrap="nowrap" align="center"><?php echo JHtml::_('jgrid.published', $item->published, $i, 'departments.'); ?></td>
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