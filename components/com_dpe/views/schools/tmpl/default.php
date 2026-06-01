<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Table\Table;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$canCreate = $this->user->authorise('core.create', 'com_multiagency');
$canEdit   = $this->user->authorise('core.edit', 'com_multiagency');
$canDelete = $this->user->authorise('core.delete', 'com_multiagency');

HTMLHelper::_('script', 'media/com_dpe/js/dpe.min.js');

$params = DPE::config();
$dateFormat = (String) $params->get('dateFormat');

// Get Actionboard Itemid
$schoolMgmtlink = 'index.php?option=com_multiagency&view=multiagencyform&layout=agencydetails';
$dpeUtility     = DPE::utilities();
$schoolItemId   = $dpeUtility->getItemId($schoolMgmtlink);

$staffUserUrl = 'index.php?option=com_multiagency&view=users';
$userItemId   = $dpeUtility->getItemId($staffUserUrl);

$slaActivitiesUrl = 'index.php?option=com_sla&view=slaactivities';
$slaItemId   = $dpeUtility->getItemId($slaActivitiesUrl);

$licenceformUrl = 'index.php?option=com_multiagency&view=licenceform&layout=edit';
$licenceformItemId   = $dpeUtility->getItemId($licenceformUrl);

$multiagencyformUrl = 'index.php?option=com_multiagency&view=multiagencyform&layout=edit';
$multiagencyformItemId   = $dpeUtility->getItemId($multiagencyformUrl);
Text::script('COM_MULTIAGECNY_ARCHIVE_LICENCE_MESSAGE');
$user = Factory::getUser();
?>

<div id="school-mgmt">
	<div class ="row">
		<form action="<?php echo Route::_('index.php?option=com_dpe&view=schools');?>" method="post" name="adminForm" id="adminForm" >
			<!--Filter-->
			<div class="row">
				<div class="col-sm-12 col-md-12 mb-3">
					<div class="input-group1 manage-staff dp-search-filter">
						<?php
							echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
						?>
					</div>
				</div>

				
				<div class="col-sm-12 col-md-12 mb-3">
					<?php if ($canCreate) : ?>

					<?php $srclink = Route::_('index.php?option=com_dpe&view=schools&tmpl=component&layout=import', false); ?>

						<a onclick="openspotlight('<?php echo $srclink; ?>')" class="btn btn-primary pull-right ml-10">
						<span class="fa fa-upload"></span> &nbsp;
						<?php echo Text::_('COM_ORGANIZATION_ENROL_IMPORT_CSV'); ?>
						</a>
						<a href="<?php echo Route::_('index.php?option=com_multiagency&task=licenceform.edit&id=0&Itemid=' .$licenceformItemId, false); ?>" class="btn btn-primary pull-right ml-10"><i class="icon-plus"></i>
							<?php echo Text::_('COM_MULTIAGENCY_ADD_ITEM_LICENCE'); ?></a>

						<a href="<?php echo Route::_('index.php?option=com_multiagency&task=multiagencyform.edit&id=0&Itemid=' . $multiagencyformItemId, false); ?>" class="btn btn-primary pull-right"><i class="icon-plus"></i>
							<?php echo Text::sprintf('COM_MULTIAGENCY_ADD_SCHOOL', Text::_('COM_MULTIAGENCY_ORGANISATION')); ?>
						</a>

					<?php endif; ?>
				</div>
			</div>

			<!--Container-->
			<div class="col-xs-12 mt-2">
				<?php
				if (!empty($this->items))
				{
				?>
				<table class="table table-striped" id="schoolList">
					<thead>
						<tr>
							<th class="center">
								<?php echo HTMLHelper::_('searchtools.sort', Text::sprintf('COM_DPE_SCHOOL_NAME', Text::_('COM_MULTIAGENCY_ORGANISATION')), 'a.title', $listDirn, $listOrder);?>
							</th>

							<th class="center">
								<?php echo Text::sprintf('COM_DPE_SCHOOL_ADMIN', Text::_('COM_MULTIAGENCY_ORGANISATION')); ?>
							</th>
							<th class="center">
								<?php echo Text::_('COM_DPE_USERS'); ?>
							</th>

							<th class="center">
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_DPE_LEAD_CONSULTANT', 'u.name', $listDirn, $listOrder);?>
							</th>

							<th class="center">
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_DPE_LICENCE_END_DATE', 'l.end_date', $listDirn, $listOrder);?>
							</th>

							<?php if ($canEdit || $canDelete): ?>
							<th class="center">
								<?php echo Text::sprintf( 'COM_DPE_SCHOOL_ACTIONS', Text::_('COM_MULTIAGENCY_ORGANISATION')); ?>
							</th>
							<th class="center">
								<?php echo Text::_( 'COM_DPE_LICENCE_ACTIONS'); ?>
							</th>
							<?php endif; ?>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach ($this->items as $i => $item)
					{
						?>
							<tr class="row<?php echo $i % 2; ?>">
								<td>
									<a href="<?php echo Route::_('index.php?option=com_multiagency&view=multiagencyform&layout=agencydetails&id='. $item->id . '&Itemid=' . $schoolItemId, false); ?>">
										<?php echo $this->escape($item->school_name);?>
									</a>
									
									<!-- Show Kb, g platform icon to dpe admin only -->
									<?php if ($user->authorise('core.manageall', 'com_cluster')) { ?>
										<?php if ($item->platform) { ?>
										 <span class="badge btn-primary pull"><?php echo $item->platform;?></span>
										<?php } ?>

											<?php if ($item->sla_name == $item->school_name.' DPO Lite' || $item->sla_name == 'DPO Lite') { ?>
										 <span class="badge btn-primary pull"><?php echo "Lite";?></span>
										<?php } ?>

										<?php if (isset($item->licence_tools['com_dpe.redaction'])) { ?>
										 <span class="badge btn-primary pull"><?php echo "R";?></span>
										<?php } ?>

									<?php } ?>
								</td>

 

								<td>
									<?php echo $this->escape($item->schooladmin);?>
								</td>

								<td>
									<a href="<?php echo Route::_($staffUserUrl . '&agencies='. $item->cluster_id . '&Itemid=' . $userItemId, false); ?>">
										<?php echo $item->users_count; ?>
									</a>
								</td>



								<td>
									<?php echo $this->escape($item->lead_consultant);?>
								</td>

								<td class="text-nowrap">
									<?php
									echo ($item->licence_end_date) ? HTMLHelper::_('date', $this->escape($item->licence_end_date), $dateFormat, false) : '';?>
								</td>

								<?php if ($canEdit || $canDelete): ?>
									<td class="center">
										<?php if ($canEdit): ?>
										<a href="<?php echo Route::_('index.php?option=com_multiagency&task=multiagencyform.edit&id=' . $item->id); ?>" class="btn btn-mini" type="button"><i class="icon-edit" ></i></a>
										<?php endif; ?>

										<?php if ($canDelete): ?>
										<a href="javascript:void(0);" data-deleteRecId="<?php echo base64_encode($item->id); ?>" class="btn btn-mini delete-button delete-record" type="button" data-formName="MultiagencyForm"><i class="icon-trash" ></i></a>
										<?php endif; ?>

									<!-- check condition if activity is avilable then only show icon -->

									<?php

									Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_activitystream/tables');
									$activityTable = Table::getInstance('Activity', 'ActivityStreamTable');
									$activityTable->load(array('target_id' => $item->licence_id, 'client' => 'com_multiagency'));

									if ($activityTable->id)
									{
										$slaActivityLink = 'index.php?option=com_dpe&view=schools&layout=default_activity';
										$showSlaActivitiesLink = Route::_($slaActivityLink . '&tmpl=component&licence_id=' . $item->licence_id);
									?>
										<a class="btn btn-mini" href="javascript:void(0);"
										onclick="openActivityPopup('<?php echo addslashes($showSlaActivitiesLink);?>','timelog-activities-popup')">
											<i class="fa fa-history fa-lg" title="<?php echo Text::_('COM_MULTIAGENCY_SLA_ACTIVITY_ICON_HELP_TEXT')?>"></i>
										</a>
									<?php
									}
									?>
									</td>
								<?php endif; ?>

								<?php if ($canEdit || $canDelete): ?>
									<td>
										<!-- If licence is archived then don't show actions -->

											<?php if ($canEdit && $item->licence_id): ?>
												<a href="<?php echo Route::_('index.php?option=com_multiagency&task=licenceform.edit&id=' . $item->licence_id.'&Itemid='.$licenceformItemId); ?>" class="btn btn-mini" type="button">
													<i class="icon-edit" aria-hidden="true"></i>
												</a>
											<?php endif; ?>

										<?php if ($item->licenceState != 2 ) : ?>

											<!-- Show archive button only for active licence -->
											<?php if ($canDelete && $item->licence_id && $item->licenceState != 3): ?>
												<a href="javascript:void(0);" data-licencerecid="<?php echo base64_encode($item->licence_id); ?>" class="btn btn-mini archive-licence" type="button"><i class="icon-trash" ></i>
												</a>
											<?php endif; ?>

											<!-- Show delete button only for upcoming licence -->
											<?php if ($canDelete && $item->licence_id && $item->licenceState == 3): ?>
												<a href="javascript:void(0);" data-deleteRecId="<?php echo base64_encode($item->licence_id); ?>" class="btn btn-mini delete-button delete-record" type="button" data-formName="LicenceForm"><i class="icon-trash" ></i>
												</a>
											<?php endif; ?>

										<?php else: ?>
											<?php echo "-"; ?>
										<?php endif; ?>
										<!-- Second if condition closed here -->
									</td>

								<?php endif; ?>

								<?php if ($item->licenceState == 2): ?>
									<td>
										<a href="javascript:void(0);" 
										class="btn btn-mini edit-licence-btn" 
										data-id="<?php echo $item->licence_id; ?>" 
										data-itemid="<?php echo $licenceformItemId; ?>" 
										data-token="<?php echo JSession::getFormToken(); ?>" 
										data-bs-toggle="tooltip" 
										title="<?php echo Text::_( 'COM_DPE_LICENCE_RENEW'); ?>">
											<i class="fas fa-undo"></i>
										</a>

										<div class="overlay" id="loader-overlay">
											<div class="loader"></div>
										</div>
									</td>
									<?php endif; ?>
							</tr>
					<?php
					}
					?>
					</tbody>
					</table>
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
				<input type="hidden" name="boxchecked" value="0" />
				<input type="hidden" name="entityName" id="entityName" value="" />
				<input type="hidden" name="entityRecordId" id="entityRecordId" value="" />
				<?php echo HTMLHelper::_( 'form.token'); ?>
				<!--Pagination Here-->
				<div class="col-xs-12">
					<div class="pager" id="pagination">
						<?php echo $this->pagination->getPagesLinks(); ?>
						<!-- <hr class="hr hr-condensed"/> -->
					</div>
				</div>
			</div>

		</form>
	</div>
</div>
<script type="text/javascript">
jQuery('#filter_symbolfilter').on('change', function() {
	jQuery('#filter_users_count').val("");
});
</script>
<!-- Added for tag filter -->
<script>

		function openspotlight(srclink)
		{			
			SqueezeBox.open( srclink,{handler: "iframe", size: {x:1000, y:550}});	

		}
	jQuery(document).ready(function(){

		// checked de admin
		var isDpeAdmin = "<?php echo $user->authorise('core.manageall', 'com_cluster'); ?>";
		if (!isDpeAdmin)
		{
			jQuery('#filter_tags_chosen').hide();
		}
	
		// update the plcaeholder
   		jQuery("#filter_tags").attr("data-placeholder", "<?php echo Text::_('COM_DPE_FORM_LBL_TAG'); ?>");
		jQuery("#filter_tags").trigger("liszt:updated");
	
	jQuery('#filter_tags').on('change', function() {
		jQuery("#filter_cluster_id").val(jQuery("#filter_cluster_id option:first").val());
    });
    jQuery('#filter_cluster_id').on('change', function() {	
		jQuery("#filter_tags").val('');
    });
})
</script>
