<?php
/**
 * @package    Com_Dpe
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Factory;

HTMLHelper::_('bootstrap.framework');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('stylesheet','modal.css');

$document = Factory::getDocument();
$document->addStyleSheet('templates/shaper_helix3/css/bootstrap.min.css');
$document->addStyleSheet('templates/shaper_helix3/css/custom.css');
$listOrder  = $this->state->get('list.ordering');
$listDirn   = $this->state->get('list.direction');
$user    = Factory::getUser();
$jlikeDelete   = $user->authorise('core.delete', 'com_jlike');
?>
<div id="system-message" class="hide assign-popup">
	
	<div class="assign-response">
	</div>
	<a class="close" data-dismiss="alert" style="float: right;margin-top: -24px;">×</a>
</div>
<div class="height-100vh">

<form action="<?php echo Route::_('index.php?option=com_dpe&view=users&layout=users&id='. $this->lessonId); ?>" method="post" name="adminForm" id="adminForm" class="deassign-userfrm form-validate">
	<div class="cust-table">
		<div class="modal-header">
			<h3><?php echo Text::sprintf('COM_DPE_DEASSIGN_USER_HEAD', ucwords($this->escape($this->title)));?></h3>
			<button type="button" class="close" onclick="closeAssignRecommendPopups();">&times;</button>
		</div>
		<div class="tab-content clearfix">
			<div class="tab-pane fade in active" id="select_user">
			<div class="row">
						<div class="col-xs-12 mb-10">
						<?php
							echo LayoutHelper::render('joomla.searchtools.default',
							array('view' => $this));
						?>
						</div>
					</div>
				<div class="modal-body">

					<?php
						if (!empty($this->items))
						{

						?>
						<div class="row user-popup">
						<table class="table" id="multiagencyList">
							<thead class="thead-fixed w-100">
								<tr>
									<th width="1%">
										<?php
											$disableCheck = '';
											if (empty($this->items)):
												$disableCheck = 'disabled'; ?>
											<?php endif;?>
										<input type="checkbox" name="checkall-toggle" value=""
											title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>"
											onclick="Joomla.checkAll(this)" <?php echo $disableCheck;?>/>
									</th>
									<th width="35%">
										<?php echo HTMLHelper::_( 'grid.sort', 'COM_DPE_ENROLMENT_USERs', 'a.name', $listDirn, $listOrder); ?>
									</th>
									<th width="30%">
										<?php echo HTMLHelper::_( 'grid.sort', Text::sprintf('COM_DPE_SCHOOL', Text::_('COM_MULTIAGENCY_ORGANISATION')), 'c.title', $listDirn, $listOrder); ?>
									</th>
									<th width="20%">
										<?php echo HTMLHelper::_( 'grid.sort', 'COM_DPE_ENROLMENT_DESIGNATION', 'r.name', $listDirn, $listOrder); ?>
									</th>
									<th width="20%">
										<?php echo Text::_('COM_DPE_ENROLMENT_DUE_DATE'); ?>
									</th>
								</tr>
							</thead>
							<tbody class="tbody-fixed w-100" id="search-tool">
								<?php foreach ($this->items as $i => $item) : ?>
								<td class="center" width="1%">
											<?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'uid'); ?>
										</td>
									<td class="user-name" width="35%"><?php echo $item->name; ?> </td>
									<td width="30%"><?php echo $this->escape($item->title); ?> </td>
									<td width="20%">										<?php
										// Get subusers actions mapp
										$coreRoleId = RBACL::getCoreRoleByUser($item->id, 'com_multiagency', $item->client_id);

										if ($coreRoleId[0])
										{
											$tjUcmRoleTable = RBACL::table('role');
											$tjUcmRoleTable->load(array('id' => $coreRoleId[0]));
										}

										if (property_exists($tjUcmRoleTable, 'name'))
										{
											echo $this->escape($tjUcmRoleTable->name);
										}
										?>
									</td>
									<td width="20%">
										<?php if ($item->todo_due_date != Factory::getDbo()->getNullDate()) : ?>
											<?php echo HTMLHelper::_('date', $item->todo_due_date, Text::_('DPE_DATE_FORMAT')); ?>
										<?php endif; ?>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						</div>
						<?php if ($jlikeDelete) { ?>
							<div class="row fixed next-btn">
								<div class="col-xs-12 text-center assign-btn">
									<a class="btn btn-blue enroll-btn pull-right" name="deassign" id="deassign"><?php echo Text::_('COM_DPE_DEASSIGNMENT_BTN'); ?></a>
								</div>
							</div>
						<?php } ?>

						<?php
						}
						else
						{
							?>
							<div class="clearfix">&nbsp;</div>
							<div class="alert alert-info"><?php echo Text::_("COM_DPE_NO_RECORDS_FOUND");?></div>
							<?php
						}
						?>
					<input type="hidden" name="task" id="task" value="" />
					<input type="hidden" name="element_id" value="<?php echo $this->lessonId;?>" />
					<input type="hidden" name="cluster_id" value="<?php echo $this->clusterId;?>" />
					<input type="hidden" name="title" value="<?php echo $this->escape($this->title);?>" />
					<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
					<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
					<input type="hidden" name="tmpl" value="component" />
					<input type="hidden" name="boxchecked" value="0" />
					<?php echo HTMLHelper::_( 'form.token'); ?>
				</div>
			</div>
		</div>
	</div>
</form>
</div>

