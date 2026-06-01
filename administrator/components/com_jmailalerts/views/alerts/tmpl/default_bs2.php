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

$user      = Factory::getUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canOrder  = $user->authorise('core.edit.state', 'com_jmailalerts');
$saveOrder = $listOrder == 'a.ordering';

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_jmailalerts&task=alerts.saveOrderAjax&tmpl=component';

	HTMLHelper::_('sortablelist.sortable', 'alertList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

Text::script('JGLOBAL_VALIDATION_FORM_FAILED');

Factory::getDocument()->addScriptDeclaration('
	Joomla.submitbutton = function(task)
	{
		if (task == "alerts.delete") {
			if (confirm("' . Text::_('COM_JMAIL_ALERTS_DELETE_CONFIRMATION') . '")) {
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

if (!empty($this->extra_sidebar))
{
	$this->sidebar .= $this->extra_sidebar;
}
?>

<div class="<?php echo JMAILALERTS_WRAPPER_CLASS; ?>" id="jmailalerts-alerts">
	<form action="<?php echo Route::_('index.php?option=com_jmailalerts&view=alerts'); ?>"
		method="post" enctype="multipart/form-data"
		name="adminForm" id="adminForm"
		class="form-validate">
		<?php
		if (!empty($this->sidebar))
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
		?>

				<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
				<div class="clearfix"> </div>

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
					<table class="table table-striped" id="alertList">
						<thead>
							<tr>
								<?php
								if (isset($this->items[0]->ordering))
								{
									?>
									<th width="1%" class="nowrap center hidden-phone">
										<?php
										echo HTMLHelper::_(
											'searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'
										);
										?>
									</th>
									<?php
								}
								?>

								<th width="1%" class="hidden-phone">
									<?php echo HTMLHelper::_('grid.checkall');?>
								</th>

								<?php
								if (isset($this->items[0]->state))
								{
									?>
									<th width="1%" class="nowrap center">
										<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
									</th>
									<?php
								}
								?>

								<th class="left">
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_JMAILALERTS_ALERTS_TITLE', 'a.title', $listDirn, $listOrder); ?>
								</th>

								<th class="center">
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_JMAILALERTS_ALERTS_IS_DEFAULT', 'a.is_default', $listDirn, $listOrder); ?>
								</th>

								<th class="center">
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_JMAILALERTS_ALERTS_DEFAULT_FREQ', 'a.default_freq', $listDirn, $listOrder); ?>
								</th>

								<th class="center">
									<?php echo Text::_('COM_JMAILALERTS_SUBSCRIPTION'); ?>
								</th>

								<th class="center">
									<?php echo Text::_('COM_JMAILALERTS_UNSUBSCRIPTION'); ?>
								</th>

								<th class="center">
									<?php echo Text::_('COM_JMAILALERTS_NOT_OPTED_USERS'); ?>
								</th>

								<?php
								if (isset($this->items[0]->id))
								{
									?>
									<th width="1%" class="nowrap center hidden-phone">
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

						<tbody>
							<?php
							foreach ($this->items as $i => $item)
							{
								$ordering   = ($listOrder == 'a.ordering');
								$canCreate  = $user->authorise('core.create', 'com_jmailalerts');
								$canEdit    = $user->authorise('core.edit', 'com_jmailalerts');
								$canCheckin = $user->authorise('core.manage', 'com_jmailalerts');
								$canChange  = $user->authorise('core.edit.state', 'com_jmailalerts'); ?>

								<tr class="row<?php echo $i % 2; ?>">
									<?php
									if (isset($this->items[0]->ordering))
									{
										?>
										<td class="nowrap center hidden-phone">
											<?php
											if ($canChange)
											{
												$disableClassName = '';
												$disabledLabel    = '';

												if (!$saveOrder)
												{
													$disabledLabel    = Text::_('JORDERINGDISABLED');
													$disableClassName = 'inactive tip-top';
												}
												?>

												<span class="sortable-handler hasTooltip <?php echo $disableClassName; ?>"
													title="<?php echo $disabledLabel; ?>">
													<i class="icon-menu"></i>
												</span>

												<input type="text" style="display:none"
													name="order[]" size="5"
													value="<?php echo $item->ordering; ?>"
													class="width-20 text-area-order " />
												<?php
											}
											else
											{
												?>
												<span class="sortable-handler inactive" >
													<i class="icon-menu"></i>
												</span>
												<?php
											}
											?>
										</td>
										<?php
									}
									?>

									<td class="center hidden-phone">
										<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
									</td>

									<?php
									if (isset($this->items[0]->state))
									{
										?>
										<td class="center">
											<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'alerts.', $canChange, 'cb'); ?>
										</td>
										<?php
									}
									?>

									<td>
										<?php
										if (isset($item->checked_out) && $item->checked_out)
										{
											?>
											<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'alerts.', $canCheckin); ?>
											<?php
										}
										?>

										<?php
										if ($canEdit)
										{
											?>
											<a href="<?php echo Route::_('index.php?option=com_jmailalerts&task=alert.edit&id=' . (int) $item->id); ?>">
											<?php echo $this->escape($item->title); ?></a>
											<?php
										}
										else
										{
											?>
											<?php echo $this->escape($item->title); ?>
											<?php
										}
										?>

										<br/>

										<span class="small">
											<strong><?php echo Text::_('COM_JMAILALERTS_PLUGINS_NAMES'); ?>:</strong>
											<br/>
											<?php echo $item->plg_names; ?>
										</span>
									</td>

									<td class="center">
										<?php echo HTMLHelper::_('jgrid.isdefault', $item->is_default != '0', $i, 'alerts.', true); ?>
									</td>

									<td class="center">
										<?php echo Text::_($item->frequencyname); ?>
									</td>

									<td class="center">
										<?php
										if (array_key_exists((int) $item->id, $this->subsreport))
										{
											?>
											<?php echo Text::_('COM_JMAILALERTS_SUBSCRIPTION_REGISTERD'); ?>

											<br/>

											<span class="label label-success">
												<?php
												echo $this->subsreport[$item->id]['registed_users'];
												$outof = $this->subsreport[$item->id]['registed_users'] +
												$this->subsreport[$item->id]['unsubscribed_users'] +
												$this->subsreport[$item->id]['not_opted_user'];
												echo ' / ' . $outof;
												?>
											</span>

											<br/><br/>

											<?php //echo Text::_('COM_JMAILALERTS_SUBSCRIPTION_GUEST'); ?>

											<br/>

										<!-- 	<span class="label label-warning">
												<?php //echo $this->subsreport[$item->id]['guest_users']; ?>
											</span>
 -->
											<?php
										}
										?>
									</td>

									<td class="center">
										<?php
										if (array_key_exists((int) $item->id, $this->subsreport))
										{
											?>
											<?php echo Text::_('COM_JMAILALERTS_SUBSCRIPTION_REGISTERD'); ?>

											<br/>

											<span class="label label-success">
												<?php echo $this->subsreport[$item->id]['unsubscribed_users'];
											echo ' / ' . $outof; ?>
											</span>

											<br/><br/>

											<?php //echo Text::_('COM_JMAILALERTS_SUBSCRIPTION_GUEST'); ?>

											<!-- <br/>

											<span class="label label-warning">
												<?php// echo $this->subsreport[$item->id]['unsub_guest_users']; ?>
											</span>
 -->
											<?php
										}
										?>
									</td>

									<td class="center">
										<span class="label label-success">
											<?php
											if (array_key_exists((int) $item->id, $this->subsreport))
											{
												echo $this->subsreport[$item->id]['not_opted_user'];
												echo ' / ' . $outof;
											}
											?>
										</span>
									</td>

									<?php
									if (isset($this->items[0]->id))
									{
										?>
										<td class="center hidden-phone">
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

				<input type="hidden" name="task" value="" />
				<input type="hidden" name="boxchecked" value="0" />
				<input type="hidden" name="list[fullorder]" value="<?php echo $listOrder . ' ' . $listDirn; ?>" />
				<?php echo HTMLHelper::_('form.token'); ?>

			</div>
	</form>
</div>
