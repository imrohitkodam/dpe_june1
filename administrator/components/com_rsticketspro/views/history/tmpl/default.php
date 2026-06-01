<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.keepalive');

$listOrder 	= $this->escape($this->state->get('list.ordering'));
$listDirn 	= $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=history&id='.$this->id.'&tmpl=component'); ?>" method="post" name="adminForm" id="adminForm">
	<table class="table table-striped">
		<thead>
		<tr>
			<th><?php echo JHtml::_('grid.sort', 'RST_HISTORY_DATE', 'date', $listDirn, $listOrder); ?></th>
			<th><?php echo JHtml::_('grid.sort', 'RST_HISTORY_IP', 'ip', $listDirn, $listOrder); ?></th>
			<th><?php echo JText::_('RST_HISTORY_ACTION'); ?></th>
			<th><?php echo JText::_('RST_HISTORY_VIEWED'); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php
		foreach ($this->items as $item)
		{
			?>
			<tr>
				<td width="1%" nowrap="nowrap"><?php echo $this->showDate($item->date); ?></td>
				<td width="1%" nowrap="nowrap"><?php echo $this->escape($item->ip); ?></td>
				<td width="1%" nowrap="nowrap"><?php echo JText::_('RST_HISTORY_ACTION_'.$item->type); ?></td>
				<td><?php echo $this->escape($this->showUser($item->user_id)); ?></td>
			</tr>
			<?php
		}
		?>
		</tbody>
	</table>
	<?php echo $this->pagination->getListFooter(); ?>

	<?php echo JHtml::_( 'form.token' ); ?>
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
</form>