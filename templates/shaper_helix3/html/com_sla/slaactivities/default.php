<?php
/**
 * @package    Sla
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('jquery.token');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
 HTMLHelper::_('formbehavior.chosen', 'select');

HTMLHelper::_('bootstrap.renderModal', 'a.tjmodal');
HTMLHelper::_('script', 'media/system/js/messages.min.js');

Text::script('COM_SLA_ACTIVITY_CONFIRM_DELETE');
Text::script('COM_SLA_ACTIVITY_CONFIRM_ARCHIVE');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$licenseId = $this->escape($this->state->get('filter.license_id'));
$saveOrder = $listOrder == 'sa.ordering';

if ( $saveOrder )
{
	$saveOrderingUrl = 'index.php?option=com_sla&task=slaactivities.saveOrderAjax';
	HTMLHelper::_('sortablelist.sortable', 'slaactivitiesList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

$options['relative'] = true;
HTMLHelper::_('script', 'com_sla/slaService.min.js', $options);
HTMLHelper::_('script', 'com_sla/sla.min.js', $options);
HTMLHelper::_('script', 'com_sla/slaActivity.js', $options);
HTMLHelper::_('script', 'com_timelog/timelog.js', $options);

// Code added for Com_timelog - start
$user = Factory::getUser();

// Com_timelog - end

$slaActivityFormLink = 'index.php?option=com_sla&view=slaactivity&layout=edit';
$slaActivityViewLink = 'index.php?option=com_sla&view=slaactivity&layout=activitydetails';

// Get null data time
$db = Factory::getDbo();
$nullDate = $db->getNullDate();

$statusOptions = array(
	'I' => Text::_('COM_SLA_INCOMPLETE_STATUS'),
	'C' => Text::_('COM_SLA_COMPLETE_STATUS'),
	'CN' => Text::_('COM_SLA_CANCELLED_STATUS')
);

$params = ComponentHelper::getParams('com_multiagency');
$groupMultiagecnyAdminId = $params->get('multiagency_admin_group', '0', 'INT');
$disablePerformAction = (!in_array($groupMultiagecnyAdminId, $user->groups)) ? false : true;

// DPE - Hack  - Start
JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
$params = DPE::config();
$dateTimeFormat = (String) $params->get('dateTimeFormat');
// DPE - Hack  - End
?>

<div class="tj-page">
	<div class="row-fluid">
		<form action="<?php echo Route::_('index.php?option=com_sla&view=slaactivities'); ?>" method="post" name="adminForm" id="adminForm" class="slaactivities-view">
			<?php
			if (!empty( $this->sidebar))
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
				<div id="j-main-container" class="row">

			<?php
			}?>

			<div class="col-sm-10 mb-20 searchtool-calendar">
				<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));?>
			</div>

			<?php
			// Check permission for add activity button
			if ($this->canCreateSlaActivity)
			{

					$application = Factory::getApplication();
					$sitemenu = $application->getMenu();
					$mainmenuItems = $sitemenu->getItems(array('unpublish-menu'), array(''));

					foreach ($mainmenuItems as $mainmenuItem) {

					    if ($mainmenuItem->alias === 'sla-activity') {
					       $menuItem = $mainmenuItem->id;
					    }
					}
				$addSlaActivityLink = Route::_($slaActivityFormLink . '&tmpl=component&licence_id=' . $licenseId.'&Itemid='.$menuItem);?>
				<div class="col-sm-2">
					<a class="btn btn-primary btn-small pull-right" href="javascript:void(0);"
					onclick="timeLog.openTimeLogPopup('<?php echo addslashes($addSlaActivityLink);?>','timelog-activities-popup')">
						<i class="icon-plus"></i><?php echo Text::_('COM_SLA_ADD_SLA_ACTIVITY'); ?>
					</a>
				</div>
			<?php
			}

			if (empty($this->items))
			{
			?>
			<div class="col-sm-12">
				<div class="alert alert-no-items">
					<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
				</div>
			</div>
			<?php
			}
			else
			{
				?>
				<div class="col-xs-12 mt-10">
					<table class="table table-responsive table-striped overflow-y" id="slaactivitiesList">
						<thead>
							<tr>
								<!-- <th width="1%" class="nowrap center hidden-phone"> -->
								<!-- </th> -->
								<th class="center">
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_SLA_SLA_ACTIVITY_LIST_VIEW_TODO_STATUS', 'todo.status', $listDirn, $listOrder); ?>
								</th>
								<th class="center">
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_SLA_SLA_ACTIVITY_LIST_VIEW_SLA_ACTIVITY', 'sa.sla_service_id', $listDirn, $listOrder); ?>
								</th>
								<th class="center">
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_SLA_SLA_ACTIVITY_LIST_VIEW_SLA_ACTIVITY_TYPE', 'sa.sla_activity_type_id', $listDirn, $listOrder); ?>
								</th>
								<th class="center">
									<?php echo TEXT::_('COM_SLA_SLA_ACTIVITY_FORM_LBL_ORGANISATION_MEMBERS'); ?>
								</th>
								<th class="center">
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_SLA_SLA_ACTIVITY_LIST_VIEW_SLA_LEAD_CONSULTANT', 'users.id', $listDirn, $listOrder); ?>
								</th>
							<!--
								<th>
									<?php // echo HTMLHelper::_('searchtools.sort', 'COM_SLA_SLA_ACTIVITY_LIST_VIEW_SLA_START_DATE', 'todo.start_date', $listDirn, $listOrder); ?>
								</th>
							-->
								<th class="center">
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_SLA_SLA_ACTIVITY_LIST_VIEW_SLA_DUE_DATE', 'todo.due_date', $listDirn, $listOrder); ?>
								</th>
							<!-- <th>
								<?php // echo HTMLHelper::_('searchtools.sort', 'COM_SLA_SLA_ACTIVITY_LIST_VIEW_SLA_IDEAL_TIME', 'todo.ideal_time', $listDirn, $listOrder); ?>
							</th> -->
							<th class="center">
								<?php echo HTMLHelper::_('searchtools.sort', Text::sprintf('COM_SLA_SLA_ACTIVITY_LIST_VIEW_SCHOOL', Text::_('COM_SLA_ORGANISATION')), 'sa.cluster_id', $listDirn, $listOrder); ?>
							</th>
							<th class="center">
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_SLA_SLA_ACTIVITY_LIST_VIEW_SLA_SPENT_TIME', 'sa.sla_service_id', $listDirn, $listOrder); ?>
							</th>
							<?php
							if ($this->canCreateSlaActivity)
							{
								?>
								<th class="center">
									<?php echo TEXT::_('COM_SLA_SLA_ACTIVITY_LIST_VIEW_ACTION'); ?>
								</th>
							<?php
							}
							?>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($this->items as $i => $item)
						{
							JLoader::import('components.com_jlike.tables.todos', JPATH_ADMINISTRATOR);
							$todoTable = Table::getInstance('Todos', 'JlikeTable');
							$todoTable->load(array('parent_id' => $item->todo_id));

							if (property_exists($todoTable, 'assigned_to'))
							{
								$item->cluster_user = $todoTable->assigned_to;
							}

							unset($canCreateActivity);
							$canCreateActivity = 0;
							$canCreateActivity = RBACL::check($this->user->id, 'com_cluster', 'core.create.activity', 'com_sla', $item->cluster_id);
							$canDeleteActivity = RBACL::check($this->user->id, 'com_cluster', 'core.delete.activity', 'com_sla', $item->cluster_id);

							if ($this->user->authorise('core.manageall', 'com_cluster'))
							{
								$canCreateActivity = true;
								$canDeleteActivity = true;
							}
							?>
							<tr class=" <?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->id; ?>">
								<td class="status-dropdown">
								<?php
									// Check permission to update the status of activity
									// Check activity is not in upcoming state
									if ($canCreateActivity && $item->state != 3)
									{
										echo JHTML::_('select.genericlist', $statusOptions, "todo-status" . $i,
										'class="todo-status" id="todo-status-' . $item->id . '" onChange="sla.updateTodo(this,' .
										$item->todo_id . ')"', "value", "text", $item->todo_status
										);
									}
									else
									{
										switch ($item->todo_status)
										{
											case "I":
												echo Text::_('COM_SLA_INCOMPLETE_STATUS');
												break;
											case "C":
												echo Text::_('COM_SLA_COMPLETE_STATUS');
												break;
											case "CN":
												echo Text::_('COM_SLA_CANCELLED_STATUS');
												break;
											default:
												echo "-";
										}
									}
								?>
								</td>
								<td class="activity-name">

									<?php
									// Check permission to show activity detail page link
									if ($canCreateActivity)
									{
										// View Activity Link
										$viewActivityLink = Route::_($slaActivityViewLink . '&tmpl=component&id=' . $item->id . '&licence_id=' . $item->license_id);
									?>
										<a class="d-inline-block mr-15"
										href="javascript:void(0);"
										onclick="timeLog.openTimeLogPopup('<?php echo addslashes($viewActivityLink);?>', 'timelog-activities-popup')">
											<?php echo $this->escape($item->sla_service_title);?>
										</a>
									<?php
									}
									else
									{
										echo $this->escape($item->sla_service_title);
									}
									?>
								</td>
								<td><?php echo $this->escape($item->activity_type_title); ?></td>
								<td>
								<?php
										if ($item->cluster_user)
										{
											echo Factory::getUser($item->cluster_user)->name;
										}
										else
										{
											echo '-';
										}
								 ?>
								</td>
								<td><?php echo $this->escape($item->uname); ?></td>
								<!--
								<td>
								<?php
									/*
									if ($item->todo_start_date != $nullDate)
									{
										echo HTMLHelper::_('date', $this->escape($item->todo_start_date), Text::_('DATE_FORMAT_FILTER_DATE'), false);
									}
									else
									{
										echo '-';
									}
									*/
								?>
								</td>
								-->
								<td class="text-nowrap">
								<?php
									if ($item->todo_due_date != $nullDate)
									{
										echo HTMLHelper::_('date', $this->escape($item->todo_due_date), $dateTimeFormat, false);
									}
									else
									{
										echo '-';
									}
								?>
								</td>
								<!-- <td><?php // echo $this->escape($item->todo_ideal_time); ?></td> -->
								<td><?php echo wordwrap($this->escape($item->school_name)); ?></td>

								<td class="text-nowrap">
									<?php
									// Check permission to show timelog
									if ($this->canCreateSlaActivity || $this->canView)
									{
										if ($item->spentTime)
										{
											$viewtimeLogLink = Route::_('index.php?option=com_timelog&view=activities&layout=activities&tmpl=component&licence_id=' .
											(int) $item->license_id . '&sla_activity=' . $item->id . '&state=' . $item->state
											);


										?>
											<a class="d-inline-block mr-15" href="javascript:void(0);"
											onclick="timeLog.openTimeLogPopup('<?php echo addslashes($viewtimeLogLink);?>')" id="assign-modal-link"
											title="<?php echo TEXT::_('COM_SLA_SLA_ACTIVITY_TIME_VIEW'); ?>">
												<?php echo ($item->spentTime);?>
											</a>
										<?php
										}
										else
										{
											echo "-";
										}

										if ($item->media)
										{
										?>
											<a class="d-inline-block mr-15" href="javascript:void(0);"
											onclick="timeLog.openTimeLogPopup('<?php echo addslashes($viewtimeLogLink);?>')" id="assign-modal-link"><i class="fa fa-file-text-o fa-lg"></i>
											</a>
										<?php
										}
									}
									else
									{
										echo ($item->spentTime) ? $item->spentTime : '-';
									}
									?>
								</td>
								<?php
								if ($canCreateActivity)
								{
								?>
									<td>
										<!-- Check timelog permission for add timelog -->
										<!-- Check activity is not in upcoming state -->
										<?php
										if ($this->canTimelog && $item->state != 3)
										{
											$addtimeLogLink = Route::_('index.php?option=com_timelog&tmpl=component&task=dpeactivityform.edit&id=0&licence_id=' .
											(int) $item->license_id . '&sla_activity=' . $item->id . '&state=' . $item->state
											);
											?>
											<a class="d-inline-block mr-15" href="javascript:void(0);"
											onclick="timeLog.openTimeLogPopup('<?php echo addslashes($addtimeLogLink);?>', 'timelog-activities-popup')" id="assign-modal-link"
											title="<?php echo TEXT::_('COM_SLA_SLA_ACTIVITY_TIME_ADD'); ?>">
											<i class="fa fa-history fa-2x" ></i>
											</a>
										<?php
										} ?>

										<?php
											$editActivityLink = Route::_($slaActivityFormLink . '&tmpl=component&id=' . $item->id . '&licence_id=' . $item->license_id . '&state=' . $item->state);
										?>
											<!-- Add Edit link -->
										<a class="d-inline-block mr-15"
										href="javascript:void(0);"
	onclick="timeLog.openTimeLogPopup('<?php echo addslashes($editActivityLink);?>', 'timelog-activities-popup')">
										<i class="icon-edit"></i>
										</a>

										<?php if ($canDeleteActivity) { ?>
											<a href="javascript:void(0);" onclick="sla.archiveActivity(this, '<?php echo $item->id; ?>', '<?php echo $item->license_id; ?>');">
												<i class="icon-trash"></i>
											</a>
										<?php } ?>
									</td>
								<?php
								}
								?>
							</tr>
						<?php
						}
						?>
					<tbody>
				</table>
					</div>
				<div class="col-xs-12">
					<div class="pager">
						<?php echo $this->pagination->getPagesLinks(); ?>
					</div>	
				</div>
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

<script type="text/javascript">
	jQuery(document).ready(function () {
		jQuery(document).on('click', 'button[data-action="clear"], button[data-action="exit"]', function () {
			document.getElementById("adminForm").submit();
		});
	});

	jQuery(document).delegate('.calendar-textfield-class', 'focusin', function(event)
	{
		event.preventDefault();
		jQuery(this).parent().siblings(':eq(0)').show();
	});

	jQuery(document).delegate('.calendar-textfield-class', 'keydown contextmenu', function()
	{
		return false;
	});
</script>
