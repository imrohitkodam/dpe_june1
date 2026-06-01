<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.keepalive');

$listOrder	= $this->escape($this->state->get('list.ordering','id'));
$listDirn	= $this->escape($this->state->get('list.direction','desc')); ?>

<form action="<?php echo Route::_('index.php?option=com_rsseo&view=gkeywords');?>" method="post" name="adminForm" id="adminForm">
	<?php echo RSSeoAdapterGrid::sidebar(); ?>
		
		<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
		
		<table class="table table-striped">
			<caption id="captionTable" class="sr-only">
				<span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
				<span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
			</caption>
			<thead>
				<th width="1%" class="hidden-phone center text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
				<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_RSSEO_GKEYWORDS_KEYWORD', 'name', $listDirn, $listOrder); ?></th>
				<th width="4%" class="center text-center hidden-phone"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'id', $listDirn, $listOrder); ?></th>
			</thead>
			<tbody>
				<?php foreach ($this->items as $i => $item) { ?>
				<tr class="row<?php echo $i % 2; ?>">
					<td class="center text-center hidden-phone"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
					<td class="nowrap has-context">
						<a href="<?php echo Route::_('index.php?option=com_rsseo&task=gkeyword.edit&id='.$item->id); ?>">
							<?php echo $this->escape($item->name); ?>
						</a> <small>(<?php echo $item->site; ?>)</small>
					</td>
					
					<td class="center text-center hidden-phone">
						<?php echo $item->id; ?>
					</td>
				</tr>
				<?php } ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="3">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>

	<?php $selector = rsseoHelper::isJ4() ? 'rsseo-logs' : 'modal-rsseo-logs'; ?>
	<?php echo HTMLHelper::_('bootstrap.renderModal', $selector, array('title' => Text::_('COM_RSSEO_GKEYWORDS_LOG'), 'footer' => $this->loadTemplate('footer'), 'bodyHeight' => 70), $this->loadTemplate('log')); ?>

	<?php echo HTMLHelper::_( 'form.token' ); ?>
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="task" value="" />
</form>