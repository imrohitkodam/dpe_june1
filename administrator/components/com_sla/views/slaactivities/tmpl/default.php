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
								<th width="1%" class="nowrap center hidden-phone"></th>
								<th width="1%" class="nowrap center hidden-phone">
									<?php echo HTMLHelper::_('searchtools.sort', '', 'sa.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
								</th>

								<th width="1%" class="center">
									<?php echo HTMLHelper::_('grid.checkall'); ?>
								</th>

								<th width="1%" class="nowrap center">
									<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'sa.state', $listDirn, $listOrder); ?>
								</th>

								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_SLA_SLA_ACTIVITY_LIST_VIEW_SLA', 'sa.sla_id', $listDirn, $listOrder); ?>
								</th>

								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_SLA_SLA_ACTIVITY_LIST_VIEW_SLA_SERVICE', 'sa.sla_service_id', $listDirn, $listOrder); ?>
								</th>

								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_SLA_SLA_ACTIVITY_LIST_VIEW_SCHOOL', 'sa.cluster_id', $listDirn, $listOrder); ?>
								</th>
								<th>
									<?php echo Text::_('COM_SLA_SLA_ACTIVITY_LIST_VIEW_TODO'); ?>
								</th>
								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_SLA_SLA_ACTIVITY_LIST_VIEW_ID', 'sa.id', $listDirn, $listOrder); ?>
								</th>
							</tr>
						</thead>
						<tfoot>
							<tr>
								<td colspan="10">
									<?php echo $this->pagination->getListFooter(); ?>
								</td>
							</tr>
						</tfoot>
						<tbody>
							<?php
							foreach ($this->items as $i => $item)
							{
								$item->max_ordering = 0;
								$ordering   = ($listOrder == 'sa.ordering');

								$canEdit    = $this->canDo->get('core.edit');

								$canCheckin = $this->canDo->get('core.edit.state');

								$canChange  = $this->canDo->get('core.edit.state');

								$canEditOwn = $this->canDo->get('core.edit.own');
								?>
								<tr class="row <?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->id; ?>">
								<td class="order nowrap center hidden-phone">
									<?php
									$iconClass = '';

									if (!$canChange)
									{
										$iconClass = ' inactive';
									}
									elseif (!$saveOrder)
									{
										$iconClass = ' inactive tip-top hasTooltip" title="' . HTMLHelper::_('tooltipText', 'JORDERINGDISABLED');
									}
									?>
									<span class="sortable-handler<?php echo $iconClass ?>">
										<span class="icon-menu" aria-hidden="true"></span>
									</span>
									<?php
									if ($canChange && $saveOrder)
									{
									?>
									<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order" />
									<?php
									}
									?>
								</td>
								<td class="center">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
								</td>
								<td class="center">
									<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'slaactivities.', $canChange, 'cb'); ?>
								</td>
								<td class="has-context">
									<div class="pull-left break-word">
										<?php if ($item->checked_out)
										{
											?>
										<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->checked_out, $item->checked_out_time, 'slaactivities.', $canCheckin); ?>
										<?php
										}
										?>
										<?php if ($canEdit || $canEditOwn)
										{
											?>
											<a class="hasTooltip" href="
											<?php echo Route::_('index.php?option=com_sla&task=slaactivity.edit&id=' . $item->id); ?>" title="
											<?php echo Text::_('JACTION_EDIT'); ?>">
											<?php echo $this->escape($item->sla_title); ?></a>
											<?php
											}
											else
											{
												?>
											<span title="<?php echo Text::sprintf('JFIELD_ALIAS_LABEL', $this->escape($item->sla_title)); ?>">
											<?php echo $this->escape($item->sla_title); ?></span>
										<?php
										}?>

									</div>
								</td>
								<td><?php echo $this->escape($item->sla_service_title); ?></td>
								<td><?php echo $this->escape($item->school_name); ?></td>
								<td><?php echo $this->escape($item->todo_id); ?></td>
								<td><?php echo (int) $item->id; ?></td>
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
