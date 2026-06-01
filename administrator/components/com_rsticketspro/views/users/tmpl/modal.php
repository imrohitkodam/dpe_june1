<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die;

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

JHtml::_('script', 'com_rsticketspro/users.js', array('relative' => true, 'version' => 'auto'));
?>
<form action="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=users&layout=modal&tmpl=component&field=' . $this->escape($this->field)); ?>" method="post" name="adminForm" id="adminForm">
	<?php
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
		<table class="table table-striped table-condensed">
			<thead>
				<tr>
					<th><?php echo JHtml::_('searchtools.sort', 'RST_NAME', 'name', $listDirn, $listOrder); ?></th>
					<th nowrap width="25%"><?php echo JHtml::_('searchtools.sort', 'JGLOBAL_USERNAME', 'username', $listDirn, $listOrder); ?></th>
					<th nowrap width="25%"><?php echo JHtml::_('searchtools.sort', 'JGLOBAL_EMAIL', 'email', $listDirn, $listOrder); ?></th>
					<th nowrap width="1%"><?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'id', $listDirn, $listOrder); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ($this->items as $item)
			{
				$alt_email = RSTicketsProHelper::getAlternativeEmail($item->id);
				?>
				<tr>
					<td><?php echo $this->escape($item->name); ?></td>
					<td><a class="pointer button-select" href="javascript:void(0);" data-user-value="<?php echo $item->id; ?>" data-user-name="<?php echo $this->escape($item->name); ?>" data-user-field="<?php echo $this->escape($this->field);?>" data-alt-email="<?php echo $this->escape($alt_email); ?>" onclick="RSTicketsProSelectUser(this);"><?php echo $this->escape($item->username); ?></a></td>
					<td><a class="pointer button-select" href="javascript:void(0);" data-user-value="<?php echo $item->id; ?>" data-user-name="<?php echo $this->escape($item->name); ?>" data-user-field="<?php echo $this->escape($this->field);?>" data-alt-email="<?php echo $this->escape($alt_email); ?>" onclick="RSTicketsProSelectUser(this);"><?php echo $this->escape($item->email); ?></a></td>
					<td><?php echo $item->id; ?></td>
				</tr>
			<?php
			}
			?>
			</tbody>
		</table>
		<?php
		echo $this->pagination->getListFooter();
	}

	echo JHtml::_('form.token');
	?>
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="field" value="<?php echo $this->escape($this->field); ?>" />
</form>