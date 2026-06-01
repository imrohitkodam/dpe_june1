<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

$userId    = $this->user->id;
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');

?>
<form action="<?php echo Route::_('index.php?option=com_timelog&view=activitytypes'); ?>" method="post"
	  name="adminForm" id="adminForm">
	<?php if (!empty($this->sidebar)): ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<?php else : ?>
		<div id="j-main-container">
			<?php endif; ?>

            <?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

			<div class="clearfix"></div>
			<table class="table table-striped" id="activitytypeList">
				<thead>
				<tr>
				<th width="1%" class="hidden-phone">
					<input type="checkbox" name="checkall-toggle" value=""
						   title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)"/>
				</th>
				<?php if (isset($this->items[0]->state)): ?>
					<th width="1%" class="nowrap center">
							<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.`state`', $listDirn, $listOrder); ?>
</th>
				<?php endif; ?>
				<th class='left'>
				<?php echo HTMLHelper::_('searchtools.sort',  'COM_TIMELOG_ACTIVITYTYPES_ID', 'a.`id`', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo HTMLHelper::_('searchtools.sort',  'COM_TIMELOG_ACTIVITYTYPES_TITLE', 'a.`title`', $listDirn, $listOrder); ?>
				</th>

				<th class='left'>
				<?php echo Text::_('COM_TIMELOG_FORM_LBL_ACTIVITYTYPE_DESCRIPTION'); ?>
				</th>

				</tr>
				</thead>
				<tfoot>
				<tr>
					<td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 10; ?>">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
				</tfoot>
				<tbody>
				<?php foreach ($this->items as $i => $item) :

					?>
					<tr class="row<?php echo $i % 2; ?>">
						<td class="hidden-phone">
							<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
						</td>
						<?php if (isset($this->items[0]->state)): ?>
							<td class="center">
								<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'activitytypes.', $this->canChange, 'cb'); ?>
</td>
						<?php endif; ?>

										<td>

					<?php echo $item->id; ?>
				</td>				<td>
				<?php if (isset($item->checked_out) && $item->checked_out && ($this->canEdit || $this->canChange)) : ?>
					<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->uEditor, $item->checked_out_time, 'activitytypes.', $this->canCheckin); ?>
				<?php endif; ?>
				<?php if ($this->canEdit) : ?>
					<a href="<?php echo Route::_('index.php?option=com_timelog&task=activitytype.edit&id='.(int) $item->id); ?>">
					<?php echo $this->escape($item->title); ?></a>
				<?php else : ?>
					<?php echo $this->escape($item->title); ?>
				<?php endif; ?>

				</td>

				<td>
					<?php echo (strlen($this->escape($item->description)) > 100 ) ? substr($this->escape($item->description), 0, 100) . '...' : $this->escape($item->description); ?>
				</td>

					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<input type="hidden" name="task" value=""/>
			<input type="hidden" name="boxchecked" value="0"/>
            <?php echo HTMLHelper::_('form.token'); ?>
		</div>
</form>
