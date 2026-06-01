<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

JFactory::getApplication()->enqueueMessage(JText::_('RST_CUSTOM_FIELD_TRANSLATE'));

$canChange  = JFactory::getUser()->authorise('customfield.edit.state', 'com_rsticketspro');
$canEdit  	= JFactory::getUser()->authorise('customfield.edit', 'com_rsticketspro');
$listOrder 	= $this->escape($this->state->get('list.ordering'));
$listDirn 	= $this->escape($this->state->get('list.direction'));
$saveOrder	= $listOrder == 'f.ordering' && $canChange;

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_rsticketspro&task=customfields.saveOrderAjax&tmpl=component';
	JHtml::_('sortablelist.sortable', 'articleList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
?>
<form action="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=customfields'); ?>" method="post" name="adminForm" id="adminForm">
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
				<th style="width:1%" class="nowrap text-center"><?php echo JHtml::_('searchtools.sort', '', 'f.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
				<th><?php echo JHtml::_('searchtools.sort', 'RST_DEPARTMENT', 'department_name', $listDirn, $listOrder); ?></th>
				<th><?php echo JHtml::_('searchtools.sort', 'RST_CUSTOM_FIELD', 'f.name', $listDirn, $listOrder); ?></th>
				<th width="1%" nowrap="nowrap"><?php echo JHtml::_('searchtools.sort', 'JPUBLISHED', 'f.published', $listDirn, $listOrder); ?></th>
				<th width="1%" nowrap="nowrap"><?php echo JHtml::_('searchtools.sort', 'RST_REQUIRED', 'required', $listDirn, $listOrder); ?></th>
				<th width="1%"><?php echo JHtml::_('searchtools.sort', 'ID', 'f.id', $listDirn, $listOrder); ?></th>
			</tr>
			</thead>
			<tbody <?php if ($saveOrder) { ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="false"<?php } ?>>
			<?php
			foreach ($this->items as $i => $item)
			{
				?>
				<tr data-draggable-group="<?php echo $item->department_id; ?>">
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
					<td width="1%" nowrap="nowrap"><?php echo $this->escape($item->department_name); ?></td>
					<td>
						<?php
						if ($canEdit)
						{
							echo JHtml::_('link', JRoute::_('index.php?option=com_rsticketspro&task=customfield.edit&id='.(int) $item->id), $this->escape($item->name));
						}
						else
						{
							echo $this->escape($item->name);
						}
						?>
					</td>
					<td width="1%" nowrap="nowrap" align="center"><?php echo JHtml::_('jgrid.published', $item->published, $i, 'customfields.', $canChange); ?></td>
					<td align="center"><?php echo JHtml::_('jgrid.state', array(
										0 => array('setrequired', 'JYES', '', '', false, 'unpublish', 'unpublish'),
										1 => array('unsetrequired', 'JNO', '', '', false, 'publish', 'publish')
									), $item->required, $i, 'customfields.', false);
									?></td>
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