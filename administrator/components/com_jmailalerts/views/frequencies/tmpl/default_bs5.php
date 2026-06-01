<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

$user      = Factory::getUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canOrder  = $user->authorise('core.edit.state', 'com_jmailalerts');
$saveOrder = $listOrder == 'a.ordering';

if ($saveOrder && !empty($this->items))
{
	$saveOrderingUrl = 'index.php?option=com_jmailalerts&task=frequencies.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
	HTMLHelper::_('draggablelist.draggable');
}

Factory::getDocument()->addScriptDeclaration('
	Joomla.submitbutton = function(task)
	{
		if (task == "freqencies.delete") {
			if (confirm("' . Text::_('COM_JMAILALERTS_FREQUENCIES_DELETE_CONFIRMATION') . '")) {
				Joomla.submitform(task, document.getElementById("adminForm"));
			}
			else {
				return false;
			}
		}
		else {
			Joomla.submitform(task, document.getElementById("adminForm"));
		}
	};
'
);
?>

<div class="<?php echo JMAILALERTS_WRAPPER_CLASS; ?>" id="jmailalerts-freqencies">
	<form action="<?php echo Route::_('index.php?option=com_jmailalerts&view=frequencies'); ?>" method="post" name="adminForm" id="adminForm">
		<div class="row">
			<div class="col-md-12">
				<div id="j-main-container" class="j-main-container">
					<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

					<div class="clearfix"> </div>

					<?php
					if (empty($this->items))
					{
						?>
						<div class="alert alert-info">
							<span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
							<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
						</div>
						<?php
					}
					else
					{
						?>
						<table class="table table-striped itemList" id="frequencyList">
							<caption class="visually-hidden">
								<?php echo Text::_('COM_JMAILALERTS_TITLE_FREQUENCIES'); ?>,
								<span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
								<span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
							</caption>

							<thead>
								<tr>
									<th class="w-1 text-center">
										<?php echo HTMLHelper::_('grid.checkall');?>
									</th>

									<?php
									if (isset($this->items[0]->ordering))
									{
										?>
										<th scope="col" class="w-1 text-center d-none d-md-table-cell">
											<?php echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
										</th>
										<?php
									}
									?>

									<?php
									if (isset($this->items[0]->state))
									{
										?>
										<th class="w-1 text-center">
											<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
										</th>
										<?php
									}
									?>

									<th scope="col" class="w-10 d-md-table-cell">
										<?php echo HTMLHelper::_('searchtools.sort', 'COM_JMAILALERTS_FREQUENCIES_NAME', 'a.name', $listDirn, $listOrder); ?>
									</th>

									<th scope="col" class="w-10 d-none d-md-table-cell text-center">
										<?php echo HTMLHelper::_('searchtools.sort', 'COM_JMAILALERTS_FREQUENCIES_DURATION', 'a.duration', $listDirn, $listOrder); ?>
									</th>

									<th scope="col" class="w-10 d-none d-md-table-cell text-center">
										<?php echo HTMLHelper::_('searchtools.sort', 'COM_JMAILALERTS_FREQUENCIES_TIME_MEASURE', 'a.time_measure', $listDirn, $listOrder); ?>
									</th>

									<?php
									if (isset($this->items[0]->id))
									{
										?>
										<th scope="col" class="w-3 d-none d-lg-table-cell">
											<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
										</th>
										<?php
									}
									?>
								</tr>
							</thead>

							<tfoot>
								<?php
								if (isset($this->items[0]))
								{
									$colspan = count(get_object_vars($this->items[0]));
								}
								else
								{
									$colspan = 10;
								}
								?>
								<tr>
									<td colspan="<?php echo $colspan; ?>">
										<?php echo $this->pagination->getListFooter(); ?>
									</td>
								</tr>
							</tfoot>

							<tbody
								<?php if ($saveOrder) : ?>
									class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true"
								<?php endif; ?>
							>

								<?php
								foreach ($this->items as $i => $item)
								{
									$ordering   = ($listOrder == 'a.ordering');
									$canCreate  = $user->authorise('core.create', 'com_jmailalerts');
									$canEdit    = $user->authorise('core.edit', 'com_jmailalerts');
									$canCheckin = $user->authorise('core.manage', 'com_jmailalerts');
									$canChange  = $user->authorise('core.edit.state', 'com_jmailalerts');
									?>

									<tr class="row<?php echo $i % 2; ?>" data-draggable-group="<?php echo 1; ?>">
										<td class="text-center">
											<?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->name); ?>
										</td>

										<?php
										if (isset($this->items[0]->ordering))
										{
											?>
												<td class="d-none d-md-table-cell text-center">
												<?php
												$iconClass = '';
												if (!$canChange)
												{
													$iconClass = ' inactive';
												}
												elseif (!$saveOrder)
												{
													$iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
												}
												?>
												<span class="sortable-handler<?php echo $iconClass ?>">
													<span class="icon-ellipsis-v" aria-hidden="true"></span>
												</span>
												<?php if ($canChange && $saveOrder) : ?>
													<input type="text" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order hidden">
												<?php endif; ?>
											</td>
											<?php
										}
										?>

										<?php
										if (isset($this->items[0]->state))
										{
											?>
											<td class="text-center">
												<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'frequencies.', $canChange, 'cb'); ?>
											</td>
											<?php
										}
										?>

										<td class="">
											<?php
											if (isset($item->checked_out) && $item->checked_out)
											{
												?>
												<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'frequencies.', $canCheckin); ?>
												<?php
											}
											?>

											<?php
											if ($canEdit)
											{
												?>
												<a href="<?php echo Route::_('index.php?option=com_jmailalerts&task=frequency.edit&id=' . (int) $item->id); ?>">
												<?php echo Text::_($this->escape($item->name)); ?>
												</a>
												<?php
											}
											else
											{
												?>
												<?php echo Text::_($this->escape($item->name)); ?>
												<?php
											}
											?>
										</td>

										<td class="d-none d-md-table-cell text-center">
											<?php echo $item->duration; ?>
										</td>

										<td class="d-none d-md-table-cell text-center">
											<?php echo Text::_($item->time_measure); ?>
										</td>

										<?php
										if (isset($this->items[0]->id))
										{
											?>
											<td class="d-none d-lg-table-cell">
												<?php echo (int) $item->id; ?>
											</td>
											<?php
										}
										?>
									</tr>
									<?php
								}
								?>
							</tbody>
						</table>
						<?php
					}
					?>
				</div>
			</div>
		</div>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="list[fullorder]" value="<?php echo $listOrder . ' ' . $listDirn; ?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
