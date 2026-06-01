<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

$listOrder	= $this->escape($this->state->get('list.ordering','id'));
$listDirn	= $this->escape($this->state->get('list.direction','asc')); ?>

<form action="<?php echo Route::_('index.php?option=com_rsseo&view=errorlinks');?>" method="post" name="adminForm" id="adminForm">
	<?php echo RSSeoAdapterGrid::sidebar(); ?>
		
		<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
		
		<table class="table table-striped">
			<caption id="captionTable" class="sr-only">
				<span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
				<span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
			</caption>
			<thead>
				<th width="1%" class="hidden-phone center text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
				<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_RSSEO_ERROR_LINK_URL', 'url', $listDirn, $listOrder); ?></th>
				<th width="4%" class="center text-center hidden-phone"><?php echo Text::_('COM_RSSEO_ERROR_LINK_REFERRALS'); ?></th>
				<th width="4%" class="center text-center hidden-phone"><?php echo HTMLHelper::_('searchtools.sort', 'COM_RSSEO_ERROR_LINK_CODE', 'code', $listDirn, $listOrder); ?></th>
				<th width="3%" class="center text-center hidden-phone"><?php echo HTMLHelper::_('searchtools.sort', 'COM_RSSEO_ERROR_LINK_URL_COUNT', 'count', $listDirn, $listOrder); ?></th>
				<th width="3%" class="center text-center hidden-phone"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'id', $listDirn, $listOrder); ?></th>
			</thead>
			<tbody>
				<?php foreach ($this->items as $i => $item) { ?>
				<tr class="row<?php echo $i % 2; ?>">
					<td class="center text-center hidden-phone"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
					<td class="nowrap has-context">
						<?php echo $this->escape($item->url); ?>
					</td>
					<td class="center text-center hidden-phone">
						<?php if ($item->referer) { ?>
						<a href="javascript:void(0)" onclick="RSSeo.showModal('<?php echo Route::_('index.php?option=com_rsseo&view=errorlinks&layout=referrals&tmpl=component&id='.$item->id); ?>');">
							<?php echo Text::_('COM_RSSEO_ERROR_LINK_REFERRALS_VIEW'); ?>
						</a>
						<?php } else { ?>
						-
						<?php } ?>
					</td>
					<td class="center text-center hidden-phone">
						<?php echo $item->code; ?>
					</td>
					<td class="center text-center hidden-phone">
						<?php echo $item->count; ?>
					</td>
					<td class="center text-center hidden-phone">
						<?php echo $item->id; ?>
					</td>
				</tr>
				<?php } ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="6">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>

	<?php echo HTMLHelper::_( 'form.token' ); ?>
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="task" value="" />
</form>

<?php echo HTMLHelper::_('bootstrap.renderModal', 'rsseoModal', array('title' => Text::_('COM_RSSEO_ERROR_LINK_REFERRALS'), 'height' => 600, 'bodyHeight' => 70)); ?>