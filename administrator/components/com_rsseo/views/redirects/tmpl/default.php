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
$listDirn	= $this->escape($this->state->get('list.direction','ASC')); ?>

<form action="<?php echo Route::_('index.php?option=com_rsseo&view=redirects');?>" method="post" name="adminForm" id="adminForm">
	<?php echo RSSeoAdapterGrid::sidebar(); ?>
		
		<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
		
		<table class="table table-striped">
			<caption id="captionTable" class="sr-only">
				<span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
				<span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
			</caption>
			<thead>
				<th width="1%" class="hidden-phone center text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
				<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_RSSEO_REDIRECTS_FROM', 'from', $listDirn, $listOrder); ?></th>
				<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_RSSEO_REDIRECTS_TO', 'to', $listDirn, $listOrder); ?></th>
				<th width="5%" class="center text-center hidden-phone"><?php echo HTMLHelper::_('searchtools.sort', 'COM_RSSEO_HITS', 'hits', $listDirn, $listOrder); ?></th>
				<th width="5%" class="center text-center hidden-phone"><?php echo HTMLHelper::_('searchtools.sort', 'COM_RSSEO_PAGES_STATUS', 'published', $listDirn, $listOrder); ?></th>
				<th width="5%" class="center text-center hidden-phone"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'id', $listDirn, $listOrder); ?></th>
			</thead>
			<tbody>
				<?php foreach ($this->items as $i => $item) { ?>
				<tr class="row<?php echo $i % 2; ?>">
					<td class="center text-center hidden-phone"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
					<td class="nowrap has-context">
						<a href="<?php echo Route::_('index.php?option=com_rsseo&task=redirect.edit&id='.$item->id); ?>">
							<?php echo $this->escape($item->from); ?>
						</a> 
					</td>
					<td class="nowrap has-context">
						<a href="<?php echo Route::_('index.php?option=com_rsseo&task=redirect.edit&id='.$item->id); ?>">
							<?php echo $this->escape($item->to); ?>
						</a>
					</td>
					
					<td class="center text-center hidden-phone">
						<?php echo $item->hits; ?>
					</td>
					
					<td class="center text-center hidden-phone">
						<?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'redirects.'); ?>
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