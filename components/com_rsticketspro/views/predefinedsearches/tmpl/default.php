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
JHtml::_('script', 'com_rsticketspro/predefinedsearches.js', array('relative' => true, 'version' => 'auto'));

JText::script('RST_DELETE_SEARCH_CONFIRM');

$listOrder 	= $this->escape($this->state->get('list.ordering'));
$listDirn 	= $this->escape($this->state->get('list.direction'));

$saveOrderingUrl = 'index.php?option=com_rsticketspro&task=predefinedsearches.saveOrderAjax&tmpl=component';

if (!empty($this->items))
{
	JHtml::_('sortablelist.sortable', 'articleList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

if ($this->params->get('show_page_heading', 1))
{
	?>
	<h1 class="rst-heading"><?php echo $this->escape($this->params->get('page_heading', $this->params->get('page_title'))); ?></h1>
	<?php
}
?>
<div class="rst-searches">
	<p>
		<button type="button" class="btn btn-danger" disabled="disabled" id="rst_delete_btn" onclick="if (confirm(Joomla.JText._('RST_DELETE_SEARCH_CONFIRM'))) Joomla.submitbutton('predefinedsearches.delete');"><?php echo JText::_('RST_DELETE'); ?></button>
	</p>
	<form action="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=predefinedsearches'); ?>" method="post" id="adminForm" name="adminForm">
		<?php
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
			<table class="table table-striped table-bordered table-hover" id="articleList">
				<thead>
					<tr>
						<th width="1%" nowrap="nowrap"><?php echo JHtml::_('grid.checkall'); ?></th>
						<th><?php echo JText::_('RST_SEARCH_NAME'); ?></th>
						<th class="text-center" align="center"><?php echo JText::_('RST_DEFAULT_SEARCH_SHORT'); ?></th>
						<th width="1%" class="nowrap text-center"><?php echo JHtml::_('searchtools.sort', '', 'ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?></th>
					</tr>
				</thead>
				<tbody class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="false">
					<?php
					foreach ($this->items as $i => $item)
					{
						?>
						<tr data-draggable-group="1">
							<td class="text-center" align="center">
								<?php echo JHtml::_('grid.id', $i, $item->id); ?>
							</td>
							<td>
								<a href="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&task=predefinedsearch.edit&id='.$item->id); ?>">
									<?php echo !empty($item->name) ? $this->escape($item->name) : '<em>'.JText::_('RST_NO_TITLE').'</em>'; ?>
								</a>
							</td>
							<td class="text-center" align="center" style="width: 1%" nowrap="nowrap"><?php echo $item->default ? JText::_('JYES') : JText::_('JNO'); ?></td>
							<td class="order text-center">
								<span class="sortable-handler"><i class="icon-menu"></i></span>
								<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order" />
							</td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>

			<div class="pagination"><?php echo $this->pagination->getPagesLinks(); ?></div>
			<?php
		}
		?>

		<?php echo JHtml::_('form.token'); ?>
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="task" value="" />
	</form>
</div>