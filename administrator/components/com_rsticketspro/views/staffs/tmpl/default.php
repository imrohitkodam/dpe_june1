<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

$canEdit  	= JFactory::getUser()->authorise('staff.edit', 'com_rsticketspro');
$listOrder 	= $this->escape($this->state->get('list.ordering'));
$listDirn 	= $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=staffs'); ?>" method="post" name="adminForm" id="adminForm">
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
		<table class="table table-striped">
			<thead>
				<tr>
					<th width="1%" nowrap="nowrap"><?php echo JHtml::_('grid.checkall'); ?></th>
					<th><?php echo JHtml::_('searchtools.sort', 'JGLOBAL_USERNAME', 'username', $listDirn, $listOrder); ?></th>
					<th><?php echo JHtml::_('searchtools.sort', 'RST_NAME', 'name', $listDirn, $listOrder); ?></th>
					<th><?php echo JHtml::_('searchtools.sort', 'JGLOBAL_EMAIL', 'email', $listDirn, $listOrder); ?></th>
					<th><?php echo JHtml::_('searchtools.sort', 'RST_GROUP', 'group_name', $listDirn, $listOrder); ?></th>
					<th><?php echo JHtml::_('searchtools.sort', 'RST_PRIORITY', 'priority_name', $listDirn, $listOrder); ?></th>
					<th width="1%"><?php echo JHtml::_('searchtools.sort', 'ID', 'u.id', $listDirn, $listOrder); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ($this->items as $i => $item)
			{
			?>
				<tr>
					<td width="1%" nowrap="nowrap"><?php echo JHtml::_('grid.id', $i, $item->id); ?></td>
					<td>
						<?php
						if ($canEdit)
						{
							echo JHtml::_('link', JRoute::_('index.php?option=com_rsticketspro&task=staff.edit&id='.(int) $item->id), $this->escape($item->username));
						}
						else
						{
							echo $this->escape($item->username);
						}
						?>
					</td>
					<td><?php echo $this->escape($item->name); ?></td>
					<td><?php echo $this->escape($item->email); ?></td>
					<td><?php echo $this->escape($item->group_name); ?></td>
					<td>
						<?php if ($item->priority_id) { ?>
							<?php echo $item->priority_name != '' ? $this->escape(JText::_($item->priority_name)) : JText::_('RST_NO_TITLE'); ?>
						<?php } else { ?>
							<?php echo JText::_('RST_ALL_PRIORITIES'); ?>
						<?php } ?>
					</td>
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