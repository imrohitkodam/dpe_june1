<?php
/**
 * @package    Cluster
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
// HTMLHelper::_('formbehavior.chosen', 'select');
if(JVERSION >= '3.0')
{
	Factory::getDocument()->getWebAssetManager()
    ->usePreset('choicesjs')
    ->useScript('webcomponent.field-fancy-select');
}

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder == 'cu.ordering';

if ( $saveOrder )
{
	$saveOrderingUrl = 'index.php?option=com_cluster&task=clusterusers.saveOrderAjax';
	HTMLHelper::_('sortablelist.sortable', 'clustersList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
?>

<div class="tj-page">
	<div class="row-fluid">
		<form action="<?php echo Route::_('index.php?option=com_cluster&view=clusterusers'); ?>" method="post" name="adminForm" id="adminForm">

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
					<table class="table table-striped" id="clustersList">
						<thead>
							<tr>
								<!-- <th width="1%" class="nowrap center hidden-phone"></th> -->
								<th width="1%" class="center">
									<?php echo HTMLHelper::_('grid.checkall'); ?>
								</th>

								<th width="1%" class="nowrap center">
									<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'cu.state', $listDirn, $listOrder); ?>
								</th>

								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_CLUSTER_USER_LIST_VIEW_NAME', 'cu.name', $listDirn, $listOrder); ?>
								</th>
								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_CLUSTER_USER_LIST_VIEW_CLUSTER', 'cu.cluster_id', $listDirn, $listOrder); ?>
								</th>
								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_CLUSTER_USERLIST_VIEW_ID', 'cu.id', $listDirn, $listOrder); ?>
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
								$ordering   = ($listOrder == 'cl.ordering');

								$canEdit    = $this->canDo->get('core.edit');
								$canCheckin = $this->canDo->get('core.edit.state');
								$canChange  = $this->canDo->get('core.edit.state');
								$canEditOwn = $this->canDo->get('core.edit.own');
								?>
								<!-- class="row  -->
								<tr class=" <?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->id; ?>">
								<td class="center">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
								</td>
								<td class="center">
									<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'clusterusers.', $canChange, 'cb'); ?>
								</td>
								<td class="has-context">
									<div class="pull-left break-word">
										<?php if ($item->checked_out)
										{
											?>
										<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->checked_out, $item->checked_out_time, 'clusterusers.', $canCheckin); ?>
										<?php
										}
										?>
										<?php if ($canEdit || $canEditOwn)
										{
											?>
											<a class="hasTooltip" href="
											<?php echo Route::_('index.php?option=com_cluster&task=clusteruser.edit&id=' . $item->id); ?>" title="
											<?php echo Text::_('JACTION_EDIT'); ?>">
											<?php echo $this->escape($item->uname); ?></a>
											<?php
											}
											else
											{
												?>
											<span title="<?php echo Text::sprintf('JFIELD_ALIAS_LABEL', $this->escape($item->uname)); ?>">
											<?php echo $this->escape($item->name); ?></span>
										<?php
										}?>

									</div>
								</td>
								<td><?php echo $this->escape($item->name); ?></td>
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
