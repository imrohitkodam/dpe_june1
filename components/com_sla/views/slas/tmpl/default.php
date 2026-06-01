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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder == 's.ordering';

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_sla&task=slas.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'slasList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
?>

<div class="tj-page">
	<div class="row-fluid">
		<form action="<?php echo Route::_('index.php?option=com_sla&view=slas'); ?>" method="post" name="adminForm" id="adminForm">
			<?php
			if (!empty( $this->sidebar))
			{
				?>
				<div id="j-sidebar-container" class="span2">
					<?php  echo $this->sidebar; ?>
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
			?>

			<?php
			// Search tools bar
			 echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
			?>
			<?php if (empty($this->items))
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
			<table class="table table-striped" id="slasList">
				<thead>
					<tr>
						<th width="1%" class="nowrap center hidden-phone"></th>
						<th>
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_SLA_LIST_VIEW_TITLE', 's.title', $listDirn, $listOrder); ?>
						</th>
						<th>
							<?php echo Text::_('COM_SLA_LIST_VIEW_DESCRIPTION'); ?>
						</th>
						<th>
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_SLA_LIST_VIEW_CREATEDBY', 's.created_by', $listDirn, $listOrder); ?>
						</th>
						<th>
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_SLA_LIST_VIEW_ID', 's.id', $listDirn, $listOrder); ?>
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
						$ordering   = ($listOrder == 's.ordering');
						$canCreate  = $this->canCreate;
						$canEdit    = $this->canEdit;
						$canCheckin = $this->canCheckin;

						$canChange  = $this->canChangeStatus;
						?>
						<tr class="row
							<?php echo $i % 2; ?>" sortable-group-id="
							<?php echo $item->id; ?>">
						<td class="has-context">
							<div class="pull-left break-word">
								<?php if ($item->checked_out)
								{
								?>
								<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->checked_out, $item->checked_out_time, 'slas.', $canCheckin); ?>
								<?php
								}
								?>
								<span title=""><?php echo $this->escape($item->title); ?></span>
							</div>
						</td>
						<td><?php echo $item->description; ?></td>
						<td><?php echo $item->uname; ?></td>
						<td><?php echo $item->id; ?></td>
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
