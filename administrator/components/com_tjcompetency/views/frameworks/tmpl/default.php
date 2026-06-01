<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
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
$saveOrder = $listOrder == 'cf.id';
$user      = Factory::getUser();
$userId    = $user->get('id');

if ( $saveOrder )
{
	// $saveOrderingUrl = 'index.php?option=com_tjcompetency&task=frameworks.saveOrderAjax';
	// HTMLHelper::_('sortablelist.sortable', 'frameworkList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
?>

<div class="tj-page">
	<div class="row-fluid">
		<form action="<?php echo Route::_('index.php?option=com_tjcompetency&view=frameworks'); ?>" method="post" name="adminForm" id="adminForm">

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
					<table class="table table-striped" id="frameworkList">
						<thead>
							<tr>
								<!-- <th width="1%" class="nowrap center hidden-phone"></th> -->

								<th width="1%" class="center">
									<?php echo HTMLHelper::_('grid.checkall'); ?>
								</th>

								<th width="1%" class="nowrap center">
									<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'cf.state', $listDirn, $listOrder); ?>
								</th>

								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_FRAMEWORK_LIST_VIEW_TITLE', 'cf.title', $listDirn, $listOrder); ?>
								</th>

								<th width="1%" class="nowrap center hidden-phone hidden-tablet">
									<?php echo Text::_('COM_COMPETENCY_COMPETENCY_FRAMEWORK_LIST_VIEW_SKILLS'); ?>
									<span class="icon-star hasTooltip" aria-hidden="true" title="<?php echo Text::_('COM_COMPETENCY_COMPETENCY_FRAMEWORK_LIST_VIEW_SKILLS'); ?>"><span class="element-invisible"><?php echo Text::_('COM_COMPETENCY_COMPETENCY_FRAMEWORK_LIST_VIEW_SKILLS'); ?></span></span>
								</th>

								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_FRAMEWORK_LIST_VIEW_CREATED_BY', 'cf.created_by', $listDirn, $listOrder); ?>
								</th>
								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_FRAMEWORK_LIST_VIEW_ID', 'cf.id', $listDirn, $listOrder); ?>
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
								$canEdit    = ($this->canDo->get('core.edit'));

								$canEditOwn = ($this->canDo->get('core.edit.own') && ($item->created_by == $userId));

								// $canCheckin = ($this->canDo->get('core.edit.state') && $canEditOwn);
								$canCheckin = $user->authorise('core.manage',     'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;

								$canChange = ($this->canDo->get('core.edit.state') && $canEditOwn);

								?>
								<tr class=" <?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->id; ?>">

								<td class="center">
									<?php
										if ($canEditOwn || $canEdit)
										{
											echo HTMLHelper::_('grid.id', $i, $item->id);
										}
									?>
								</td>
								<td class="center">
									<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'frameworks.', $canChange, 'cb'); ?>
								</td>
								<td class="has-context">
									<div class="pull-left break-word">
										<?php if ($item->checked_out)
										{
											?>
										<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->checked_out, $item->checked_out_time, 'frameworks.', $canCheckin); ?>
										<?php
										}
										?>
										<?php if ($canEdit || $canEditOwn)
										{
											?>
											<a class="hasTooltip" href="
											<?php echo Route::_('index.php?option=com_tjcompetency&task=framework.edit&id=' . $item->id); ?>" title="
											<?php echo Text::_('JACTION_EDIT'); ?>">
											<?php echo $this->escape($item->title); ?></a>
											<?php
											}
											else
											{
												?>
											<span title="<?php echo Text::sprintf('JFIELD_ALIAS_LABEL', $this->escape($item->title)); ?>">
											<?php echo $this->escape($item->title); ?></span>
										<?php
										}?>

									</div>
								</td>
								<td class="center btns hidden-phone hidden-tablet">
									<div class="btn-group">
									<a class="badge <?php if ($item->skill_count > 0) echo 'badge-success'; ?>" title="<?php echo Text::_('COM_COMPETENCY_COMPETENCY_FRAMEWORK_LIST_VIEW_TOTAL_SKILLS'); ?>" href="<?php echo JRoute::_('index.php?option=com_tjcompetency&view=skills&framework_id=' . $item->id); ?>">
										<?php echo $item->skill_count; ?></a>
										<div class="btn-group" style="margin-left: 5px; margin-bottom: 3px;">
											<a href="<?php echo JRoute::_('index.php?option=com_tjcompetency&view=skill&layout=edit&framework_id=' . $item->id); ?>" title="<?php echo Text::_('COM_COMPETENCY_COMPETENCY_FRAMEWORK_LIST_VIEW_ADD_SKILLS'); ?>" class="badge badge-success btn-success">
												&#43;
											</a>
										</div>
									</div>
								</td>
								<td><?php echo $this->escape($item->uname); ?></td>
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
