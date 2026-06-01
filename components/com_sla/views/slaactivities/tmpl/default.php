<?php
/**
 * @package    Sla
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('jquery.token');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder == 'sa.ordering';

if ( $saveOrder )
{
	$saveOrderingUrl = 'index.php?option=com_sla&task=slaactivities.saveOrderAjax';
	HTMLHelper::_('sortablelist.sortable', 'slaactivitiesList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

$options['relative'] = true;
HTMLHelper::_('script', 'com_sla/slaService.js', $options);
HTMLHelper::_('script', 'com_sla/sla.js', $options);
?>

<div class="tj-page">
	<div class="row-fluid">
		<form action="<?php echo Route::_('index.php?option=com_sla&view=slaactivities'); ?>" method="post" name="adminForm" id="adminForm">

			<?php if (!empty( $this->sidebar))
			{
			?>
				<div id="j-sidebar-container" class="span2">
					<?php echo $this->sidebar; ?>
				</div>
				<div id="j-main-container" class="span10">
			<?php
			}
			else
			{
				?>
				<div id="j-main-container">
			<?php
			}
			// Search tools bar
			echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
			?>
			<?php
			if (empty($this->items))
			{
			?>
				<div class="alert alert-no-items">
					<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
				</div>
			<?php
			}
			else
			{
				?>
					<table class="table table-striped" id="slaactivitiesList">
						<thead>
							<tr>
								<th width="1%" class="nowrap center hidden-phone">
								</th>
								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_SLA_SLA_ACTIVITY_LIST_VIEW_SLA_ACTIVITY', 'sa.sla_service_id', $listDirn, $listOrder); ?>
								</th>
								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_SLA_SLA_ACTIVITY_LIST_VIEW_TODO_STATUS', 'todo.status', $listDirn, $listOrder); ?>
								</th>
								<!-- <th>
									<?php // echo HTMLHelper::_('searchtools.sort', 'COM_SLA_SLA_ACTIVITY_LIST_VIEW_SLA_IDEAL_TIME', 'todo.ideal_time', $listDirn, $listOrder); ?>
								</th> -->
								<th>
									<?php echo HTMLHelper::_('searchtools.sort', Text::sprintf('COM_SLA_SLA_ACTIVITY_LIST_VIEW_SCHOOL', Text::_('COM_SLA_ORGANISATION')), 'sa.cluster_id', $listDirn, $listOrder); ?>
								</th>
								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_SLA_SLA_ACTIVITY_LIST_VIEW_SLA_SPENT_TIME', 'sa.sla_service_id', $listDirn, $listOrder); ?>
								</th>
								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_SLA_SLA_ACTIVITY_LIST_VIEW_SLA_LOG_TIME', 'sa.sla_service_id', $listDirn, $listOrder); ?>
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
							<?php
							foreach ($this->items as $i => $item)
							{
								// Get Todo Status
								$todoStatusChecked = '';

								if ($item->todo_status == 'C')
								{
									$todoStatusChecked = 'checked=checked';
								}

								/*$canEdit    = $this->canDo->get('core.edit');

								$canCheckin = $this->canDo->get('core.edit.state');

								$canChange  = $this->canDo->get('core.edit.state');

								$canEditOwn = $this->canDo->get('core.edit.own');*/
								?>
								<tr class="row <?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->id; ?>">
								<td><?php echo $this->escape($item->sla_service_title); ?></td>
								<td><input onclick="sla.updateTodo(this);" type="checkbox" class="todo-status" value="<?php echo $item->todo_id; ?>" id="todo-status-<?php echo $item->id; ?>" <?php echo $todoStatusChecked; ?>  /></td>
								<!-- <td><?php // echo $this->escape($item->todo_ideal_time); ?></td> -->
								<td><?php echo $this->escape($item->school_name); ?></td>
								<td><?php echo '-'; ?></td>
								<td><?php echo '-'; ?></td>
							</tr>
							<?php
								}
							?>
						<tbody>
					</table>
					<?php
					}
					?>

					<input type="hidden" name="task" value="" />
					<input type="hidden" name="boxchecked" value="0" />
					<?php echo HTMLHelper::_('form.token'); ?>
			</div>
		</form>
	</div>
</div>
