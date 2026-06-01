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

$app        = JFactory::getApplication();
$input      = $app->input;
$scaleSetId = $input->getInt('scale_set_id');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder == 'a.id';

if ( $saveOrder )
{
	// $saveOrderingUrl = 'index.php?option=com_tjcompetency&task=scales.saveOrderAjax';
	// HTMLHelper::_('sortablelist.sortable', 'scaleList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
?>

<div class="tj-page">
	<div class="row-fluid">
		<form action="<?php echo Route::_('index.php?option=com_tjcompetency&view=scales'); ?>" method="post" name="adminForm" id="adminForm">

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
					<table class="table table-striped" id="scaleList">
						<thead>
							<tr>
								<!-- <th width="1%" class="nowrap center hidden-phone"></th> -->

								<th width="1%" class="center">
									<?php echo HTMLHelper::_('grid.checkall'); ?>
								</th>

								<th width="1%" class="nowrap center">
									<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
								</th>

								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SCALE_LIST_VIEW_TITLE', 'a.title', $listDirn, $listOrder); ?>
								</th>

								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SCALE_LIST_VIEW_SCALESET', 'a.scale_set_id', $listDirn, $listOrder); ?>
								</th>

								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SCALE_LIST_VIEW_SEQUENCE_NUMBER', 'a.sequence_number', $listDirn, $listOrder); ?>
								</th>

								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SCALE_LIST_VIEW_CREATED_BY', 'a.created_by', $listDirn, $listOrder); ?>
								</th>
								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SCALE_LIST_VIEW_ID', 'a.id', $listDirn, $listOrder); ?>
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

								$canEditOwn = ($this->canDo->get('core.edit.own') && ($item->created_by == Factory::getUser()->id));

								$canCheckin = ($this->canDo->get('core.edit.state') && $canEditOwn) || $this->canDo->get('core.edit');

								$canChange = ($this->canDo->get('core.edit.state') && $canEditOwn) || $this->canDo->get('core.edit');

								?>
								<tr class=" <?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->id; ?>">

								<td class="center">
									<?php
										//if ($canEditOwn || $canEdit)
										{
											echo HTMLHelper::_('grid.id', $i, $item->id);
										}
									?>
								</td>
								<td class="center">
									<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'scales.', $canChange, 'cb'); ?>
								</td>
								<td class="has-context">
									<div class="pull-left break-word">
										<?php if ($item->checked_out)
										{
											?>
										<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->checked_out, $item->checked_out_time, 'scales.', $canCheckin); ?>
										<?php
										}
										?>
										<?php //if ($canEdit || $canEditOwn)
										{
											?>
											<a class="hasTooltip" href="
											<?php echo Route::_('index.php?option=com_tjcompetency&task=scale.edit&id=' . $item->id); ?>" title="
											<?php echo Text::_('JACTION_EDIT'); ?>">
											<?php echo $this->escape($item->title); ?></a>
											<?php
											}
											/*else
											{
												?>
											<span title="<?php echo Text::sprintf('JFIELD_ALIAS_LABEL', $this->escape($item->title)); ?>">
											<?php echo $this->escape($item->title); ?></span>
										<?php
										} */?>

									</div>
								</td>
								<td><?php echo $this->escape($item->scaleset_title); ?></td>
								<td><?php echo $this->escape($item->sequence_number); ?></td>
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
					<input type="hidden" name="scale_set_id" value="<?php echo $scaleSetId; ?>" />
					<?php echo HTMLHelper::_('form.token'); ?>
			</div>
		</form>
	</div>
</div>
