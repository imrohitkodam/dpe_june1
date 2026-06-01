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

JHtml::_('script', 'plg_system_rsticketsprocron/cronlog.js', array('relative' => true, 'version' => 'auto'));

JText::script('RST_CONFIRM_DELETE_ALL');
?>
<form action="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=cronlog'); ?>" method="post" name="adminForm" id="adminForm">
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
					<th width="1%" nowrap="nowrap" class="center" align="center"><?php echo JText::_( '#' ); ?></th>
					<th width="1%" nowrap="nowrap" class="center" align="center"><?php echo JHtml::_('grid.checkall'); ?></th>
					<th><?php echo JHtml::_('searchtools.sort', 'RST_ACCOUNT_NAME', 'a.name', $listDirn, $listOrder); ?></th>
					<th><?php echo JHtml::_('searchtools.sort', 'DATE', 'al.date', $listDirn, $listOrder); ?></th>
					<th><?php echo JHtml::_('searchtools.sort', 'SUBJECT', 'al.subject', $listDirn, $listOrder); ?></th>
					<th><?php echo JText::_('DESCRIPTION'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($this->items as $i => $item) { ?>
				<tr>
					<td width="1%" nowrap="nowrap" class="center" align="center"><?php echo $this->pagination->getRowOffset($i); ?></td>
					<td width="1%" nowrap="nowrap" class="center" align="center"><?php echo JHtml::_('grid.id', $i, $item->id); ?></td>
					<td><?php echo $this->escape($item->name); ?></td>
					<td nowrap="nowrap"><?php echo $this->showDate($item->date); ?></td>
					<td><?php echo $this->escape($item->subject); ?></td>
					<td><?php echo str_replace('[FATAL ERROR]', '<strong class="rst_required invalid">[FATAL ERROR]</strong>', $this->escape($item->description)); ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		<?php
		echo $this->pagination->getListFooter();
	}
	?>
	</div>
	<div>
		<?php echo JHtml::_( 'form.token' ); ?>
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="task" value="" />
	</div>
</form>