<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Form;


HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('bootstrap.renderModal');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('jquery.token');
HTMLHelper::_('script', 'media/com_dpe/js/annualreport.js');
HTMLHelper::_('script', 'media/com_dpe/js/dpecanvas.js');

HTMLHelper::_('script', 'media/system/js/messages.min.js');
$document = Factory::getDocument();
$document->addStylesheet('templates/shaper_helix3/css/custom.css');
$document->addStyleSheet('media/com_dpe/css/annualreport.css');
$user      = Factory::getUser();

$dpeAdmin       = $user->authorise('core.manageall', 'com_cluster');
$params         = ComponentHelper::getParams('com_multiagency');
$orgAdminRoleId = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
$isOrgAdmin     = in_array($orgAdminRoleId, $user->groups);


$staffRole = ComponentHelper::getParams('com_multiagency')->get('member_role_id');
$annualReportsFilterConfig = ComponentHelper::getParams('com_dpe')->get('configForAnnualFilters');


// Check user having staff role org
foreach ($this->userClusters as $cluster)
{
	$coreRoleId = RBACL::getCoreRoleByUser($user->id, 'com_cluster', $cluster->cluster_id);

	if (in_array($staffRole, $coreRoleId))
	{
		$clusterIds[] = $cluster->cluster_id;
	}
}


$logsandIncidentManagement = json_decode($annualReportsFilterConfig)->configForAnnualFilters->Logs_And_Incident_Management;
$userActivity = json_decode($annualReportsFilterConfig)->configForAnnualFilters->User_Activity;
$organisationalCompliacne = json_decode($annualReportsFilterConfig)->configForAnnualFilters->Organisational_Compliance;

$input     = Factory::getApplication();
$reportId  = $input->input->get("id", '', 'INT');
?>

	<div id="ajax-preloader" class="overlay">
    <div class="loader"></div>
    <p>Sending...</p>
	</div>
	<div id="system-message-container"></div>
	<div class="overlay" id="loader-overlay">
		<div class="loader"></div>
	</div>
	<div class="container-fluid" >
		<form id="annualreporttmp" name="annualreporttmp">

			<div class="row">


				<div class="col-xs-12 col-md-3 report-section mt-3 pt-2">
					<?php if (empty($clusterIds)) {?>
						<h4 class="fw-bold mt-3 mb-2" style="color: #0e9cd1;margin-bottom:10px;"> <?php echo Text::_('COM_DPE_ANNUAL_REPORT_FILTER_HEADER'); ?></h4>
						<div>

							<p class="check-reports">
								<input type="checkbox" id="jform_check_all">
								<label for="checkall" class="checkreport-title mt-4">
									<?php echo Text::_('COM_DPE_ANNUAL_REPORT_CHECK_ALL'); ?>
								</label>
							</p>

							<h3 class="fw-bold fs-5"><?php echo Text::_('COM_DPE_ANNUAL_REPORT_LOGS_INCIDENT'); ?></h3>
							<?php foreach ($logsandIncidentManagement as $key => $value) { 
								$formattedKey = str_replace("_", " ", $key);
								?>
								<p>
									<input type="checkbox" onchange=""id="<?php echo htmlspecialchars($key); ?>" 
									name="<?php echo htmlspecialchars($key); ?>" 
									value="<?php echo htmlspecialchars(json_encode($value)); ?>"<?php echo (($this->items->created_by != $user->id) && ($reportId)) ? '' : ''; ?>>
									<label for="<?php echo htmlspecialchars($key); ?>">
										<?php echo htmlspecialchars($formattedKey); ?>
									</label>
								</p>
							<?php } ?>


							<h3 class="fw-bold fs-5"><?php echo Text::_('COM_DPE_ANNUAL_REPORT_USER_ACTIVITY'); ?></h3>
							<?php foreach ($userActivity as $key => $value) { 
								$formattedKey = str_replace("_", " ", $key);
								?>
								<p>
									<input type="checkbox" id="<?php echo htmlspecialchars($key); ?>" 
									name="<?php echo htmlspecialchars($key); ?>" 
									value="<?php echo htmlspecialchars(json_encode($value)); ?>"<?php echo (($this->items->created_by != $user->id) && ($reportId)) ? '' : ''; ?>>
									<label for="<?php echo htmlspecialchars($key); ?>">
										<?php echo htmlspecialchars($formattedKey); ?>
									</label>
								</p>
							<?php } ?>

							<h3 class="fw-bold fs-5"><?php echo Text::_('COM_DPE_ANNUAL_REPORT_ORGANISATIONAL_COMPLIANCE'); ?></h3>
							<?php foreach ($organisationalCompliacne as $key => $value) { 
								$formattedKey = str_replace("_", " ", $key);
								?>
								<p>
									<input type="checkbox" id="<?php echo htmlspecialchars($key); ?>" 
									name="<?php echo htmlspecialchars($key); ?>" 
									value="<?php echo htmlspecialchars(json_encode($value)); ?>"<?php echo (($this->items->created_by != $user->id) && ($reportId)) ? '' : ''; ?>>
									<label for="<?php echo htmlspecialchars($key); ?>">
										<?php echo htmlspecialchars($formattedKey); ?>
									</label>
								</p>
							<?php } ?>

						</div>
					<?php } ?>
				</div>
				<div class="col-xs-12 col-md-9 report-organisation-section mt-3 p-3">
					<div class="select-filter-section">
						<h4 class="mt-2 mb-4 fw-bold" style="color: #0e9cd1;"><?php echo Text::_('COM_DPE_ANNUAL_REPORT_HEADER'); ?></h4>
						<?php echo $this->form->renderField('cluster_id'); 

						$params    = ComponentHelper::getParams('com_multiagency');
						$multiagency_trustee_group = (int) $params->get('multiagency_trustee_group');
						$isTrustee 				   = in_array($multiagency_trustee_group, $user->groups);
						$orgAdminRoleId            = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
						$orgAdminRoleId 		   = in_array($orgAdminRoleId, $user->groups);

						if ($user->authorise('core.manageall', 'com_cluster') || $isTrustee || $orgAdminRoleId)
						{
							FormHelper::addFieldPath(JPATH_SITE . '/components/com_tjucm/models/fields/');
							$dpeTags = FormHelper::loadFieldType('dpetags', false);
							$dpeTag  = $dpeTags->getOptions(); 

							JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
							$dpeModel = DPE::model('school', array('ignore_request' => true));

							$trusteeTags = ($isTrustee)?$dpeModel->getAgencyTags($multiagency_trustee_group):$dpeModel->getAgencyTags($orgAdminRoleId);
							?>

							<label style="margin-right: 50px;margin-top: 20px;" id="tag_label">Tags</label><br>
							<fieldset id="filter-bar" class="controls">
								<div class="filter-select fltrt">
									<select name="filter_tags[]" id = "filter_tags"  data-placeholder="<?php echo Text::_('COM_DPE_FORM_LBL_TAG'); ?>" class="chosen-select" multiple="multiple">
										<?php if ($user->authorise('core.manageall', 'com_cluster'))
										{
											echo HTMLHelper::_('select.options', $dpeTag, 'value', 'text', $this->items->tags);
										}else{ 
											echo HTMLHelper::_('select.options', $trusteeTags, 'value', 'text', $this->items->tags);
										}?>
									</select>
								</div>
							</fieldset>
							<?php
						}
						?>


						<div class="filter_org_date pt-2" style="display: flex; gap: 50px; align-items: center; flex-wrap: wrap;">
							<?php echo $this->form->renderField('start_date'); ?>
							<?php echo $this->form->renderField('end_date'); ?>
							<h2 style="font-size: 18px; font-weight: 400;"><?php  echo Text::_('COM_DPE_ANNUAL_REPORT_OR');?></h2>
							<?php echo $this->form->renderField('date_range'); ?>
						</div>
						<div class="allreport-btn d-flex flex-wrap align-items-center gap-2 justify-content-end pt-2 px-5 mx-3">
							<!-- if(checkvalidUser('<?php echo $user->id; ?>','<?php echo $this->items->created_by; ?>','<?php echo $reportId;?>')) { showReport(this); } -->
							<a href="#" onclick="showReport(this);" class="btn btn-primary p-2"> <?php echo Text::_("COM_DPE_ANNUAL_SHOW_REPORT"); ?></a>
							<div class="dropdown downloadpdf <?php echo ($reportId)?'':'hide'?>">
								<button class="btn btn-primary p-2" type="button" id="downloadDropdowns"  aria-expanded="false" <?php echo ($reportId) ? '' : ''; ?>>
									<i class="fa fa-download" aria-hidden="true"></i>
									<?php echo Text::_('COM_DPE_REPORT_EXPORT_DOWNLOAD');?>
									<ul class="dropdown-menu pdfbtn" aria-labelledby="">
										<li>
											<a class="dropdown-item" id="downloadImage" ><i class="fa fa-file-pdf-o"></i>
												<?php echo Text::_('COM_DPE_REPORT_PDF_DOWNLOAD');?>

											</a>
										</li>
									<!-- <li>
										<a class="dropdown-item" onclick="getWordreport()">
											<i class="fa fa-file-word-o" aria-hidden="true"></i><?php echo Text::_('COM_DPE_REPORT_WORD_DOWNLOAD');?>
										</a>
									</li> -->
								</ul>
							</div>
							<div id="savebtn" class="control-group float-end hide">

								<?php if(($reportId))
								{?>
									<a class="btn btn-primary" style="padding:8px;"onclick="saveReportData();">
										<?php echo Text::_('COM_DPE_ANNUAL_REPORT_SAVE_ITEM');?>
									</a>
									<!-- <a class="btn btn-primary"onclick="saveReportData('<?php //echo Route::_('index.php?option=com_dpe&view=annualreports', false);?>');" >
										<?php //echo Text::_('COM_DPE_ANNUAL_REPORT_SAVE_CLOSE_ITEM');?>
									</a> -->
									<?php 			
								}elseif(!$reportId){?>

									<a class="btn btn-primary" style="padding:8px;"
									onclick="saveReportData();">
									<?php echo Text::_('COM_DPE_ANNUAL_REPORT_SAVE_ITEM');?>
								</a>
								<!-- <a class="btn btn-primary"onclick="saveReportData('<?php //echo Route::_('index.php?option=com_dpe&view=annualreports', false);?>');" > -->
									<?php //echo Text::_('COM_DPE_ANNUAL_REPORT_SAVE_CLOSE_ITEM');?>
									<!-- </a> --> 
								<?php }?>

								<?php if ($isOrgAdmin)
								{?>
									<a class="btn btn-primary" style="padding:8px;"onclick="sendToDpoForReview(<?php echo $reportId;?>);">
										<?php echo Text::_('COM_DPE_ANNUAL_REPORT_SEND_TO_DPO');?>
									</a>
								<?php }else if (!$isOrgAdmin && $dpeAdmin) {?>
									<a class="btn btn-primary" style="padding:8px;"onclick="sendToOrgAdmin(<?php echo $reportId;?>);">
										<?php echo Text::_('COM_DPE_ANNUAL_REPORT_SEND_TO_ORGADMIN');?>
									</a>
								<?php } ?>

							</div>
							<a class="btn btn-danger p-2" href="<?php echo Route::_('index.php?option=com_dpe&view=annualreports', false);?>">
								<?php echo Text::_('COM_DPE_ANNUAL_REPORT_CANCEL');?>
							</a>
						</div>
						
						<hr>
						<div class="tablepie-section">
							<div id="reportDetails" class="reportdata hide bg-light p-2 shadow-sm rounded-2 text-center">
								<!--ReportTile-->


								<h4 class="mb-4"><strong class="text-info"><?php echo Text::_('COM_DPE_ANNUAL_REPORT_TITLE');?></strong></br></br><?php echo '<p class="reportTitle">'. Text::_('COM_DPE_ANNUAL_REPORT_HEADER')."</p>";?> </h4>
								<h4 class="mb-4"><strong class="text-info"><?php echo Text::_('COM_DPE_ORGANISATION');?></strong></br></br><?php echo "<p class='Organisation_name'>". $this->items->cluster_id_name."</p>";?> </h4>

								<h4 class="mb-4"> <strong class="text-info"><?php echo Text::_('COM_DPE_ANNUAL_DATE_REPORT_CREATED');?></strong></br></br><?php echo "<p class='reportCreatedDate'>".Factory::getDate($this->items->created_date)->format('d-m-Y')."</p>";?></h4>

								<h4 class="mb-4"> 
									<strong class="text-info"><?php echo Text::_('COM_DPE_ANNUAL_REPORT_DATE_RANGE'); ?></strong><br></br>
									<?php 

									echo "<p id='reportCreatedDateRange'class='reportCreatedDate'>" . 
									Factory::getDate($this->items->start_date)->format('d-m-Y') . ' - ' . 
									Factory::getDate($this->items->end_date)->format('d-m-Y') . 
									"</p>"; 
									?>
								</h4>


								


								<h4 class="mb-4"><strong class="text-info"><?php echo Text::_('COM_DPE_ANNUAL_REPORT_REQUESTED_BY');?></strong></br></br><?php echo "<p class='reportCreatedBy'>". $user->name."</P>";?> </h4>
								</div>
								<div id="reporttablesection">
									<div id="Logs_And_Incident_Management"></div>
									<div id="User_Activity"></div>
									<div id="Organisational_Compliance"></div>
									<div id="Dpo_Summary"  class="hide">
										<?php echo $this->form->renderField('reportStatus'); ?>
										<?php

										$dpeAdmin      = $user->authorise('core.manageall', 'com_cluster');
										$orgAdmin      = ComponentHelper::getParams('com_multiagency')->get('school_admin_role_id');
										?>
										<?php 
										if ($dpeAdmin) {
											echo $this->form->getLabel('dpo_comment');

											echo $this->form->renderField('dpo_comment');
										} else {

											echo $this->form->getLabel('dpo_comment').': <br/>';

											if (!empty($this->items->dpo_comment)) {
												echo '<div class="dpofeedback mt-4 mb-4" style="width: 97%; padding: 10px; border: 1px solid lightgrey; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);">' 
												. $this->items->dpo_comment . 
												'</div>';
											}
										}
										?>
										<div id="dpolist" class="hide">
											<?php echo ($isOrgAdmin)?Text::_('COM_DPE_ANNUAL_REPORT_DPO_LIST'):'';?>
										</div>
									</div>
								</div>

							</div>
						</div>


					</div>
					<input type="hidden" id='report_id' name=jform[id] value="<?php echo $reportId;?>">
					<input type="hidden" id="startDate" value="<?php echo $this->items->start_date;?>">
					<input type="hidden" id="endDate" value="<?php echo $this->items->end_date;?>">
					<input type="hidden" id="orgadmin" value="<?php echo $isOrgAdmin;?>">
				</form>
			</div>
		<div id="popup-container" style="display: none;">
			<button type="button" class="close-button btn btn-group" onclick="closePopup()" aria-label="Close" style="float: right;">×</button>

		   <form id="admin-select-form">
		    <label for="admin-multiselect" class="form-label">
		        <?php echo Text::_('COM_DPE_ANNUAL_REPORT_SEND_REPORT_TO_ADMIN_SELECT');?>
		    </label>
		    
		    <select id="admin-multiselect" class="chosen-select form-select mb-3" multiple data-placeholder="Choose admins...">
		    </select>

		    <div class="d-flex justify-content-end">
		        <a class="btn btn-primary ms-3" style="padding: 8px 16px;margin-top: 26px;" onclick="sendToOrgAdminForReview(<?php echo $reportId;?>);">
		            <?php echo Text::_('COM_DPE_ANNUAL_REPORT_SEND_TO_ADMIN');?>
		        </a>
		    </div>
		</form>
		</div>
			<div class="popup-overlay" id="popupOverlay"></div>

			<div class="popup" id="popup">
				<p id="popmsgData"><?php echo Text::_('COM_DPE_SHOWMESSAGE_TO_SAVE_THE_REPORT_BEFORE_EXPORT','Warning');?></p>
				<button class="save-btn" onclick="saveAndExportPDF()">Save & Export PDF</button>
				<button class="close-btn" onclick="closePopup()">Close</button>
			</div>

			<script>
				function showPopup() {
					document.getElementById('popup').style.display = 'block';
					document.getElementById('popupOverlay').style.display = 'block';
				}

				function closePopup() {
					document.getElementById('popup').style.display = 'none';
					document.getElementById('popupOverlay').style.display = 'none';
				}
			</script>
			<?php 


			$sectionFilter = $this->items->section_filters;

			$mergedArray = array_merge((array)$logsandIncidentManagement, (array)$userActivity, (array)$organisationalCompliacne);
			$matchingReportData = array_intersect_key((array)json_decode($sectionFilter), $mergedArray);

			$clusterIds = explode(',', $this->items->cluster_ids);
			$tags = $this->items->tags;
			$dateRange = json_decode($sectionFilter)->jform->date_range;
			$matchingReportData['jform'] = array(
				'id'=>$this->items->id,
				'cluster_id' => $clusterIds,
				'start_date' => (!$dateRange) ? $this->items->start_date : '',
				'end_date' => (!$dateRange) ? $this->items->end_date : '',
				'dpo_comment' => $this->items->dpo_comment,
				'date_range' => ($dateRange) ? $dateRange : '',
				'reportStatus' => $this->items->report_status,
				'leadConsultantDropdown'=>json_decode($sectionFilter)->jform_leadConsultantDropdown
			);
			$jsonReportData = json_encode($matchingReportData);

			?>
			<script>

				jQuery(document).ready(function(){


					if( jQuery('#report_id').val().length > 0)
					{
						var reportValue = <?php echo $jsonReportData; ?>;
						showReportData(reportValue);
					}
					var tags = <?php echo json_encode($tags);?>
					
					if (tags.split(',').length > 1) {
						setTimeout(function(){
					    jQuery('#jform_cluster_id').val('').trigger('chosen:updated');
					    var tagArray = tags.split(',');
						jQuery('#filter_tags').val(tagArray).trigger('chosen:updated');
						},1000)
					}

				})




				jQuery('#downloadDropdowns').click(function (e) {
    e.preventDefault(); // Prevent default button behavior if needed
    
    const pdfBtnUl = jQuery('.pdfbtn');

    pdfBtnUl.toggleClass('show');
    jQuery('.pdfbtn').css({
    	'margin-top': '10px',
    	'padding': '10px'
    });

});
				jQuery(document).ready(function(){

					var userId ="<?php echo $user->id;?>";
					var reportCreatedUserId = "<?php echo $this->items->created_by; ?>";
					var reportId = "<?php echo $reportId;?>";

					if ((userId != reportCreatedUserId) && (reportCreatedUserId) && (!reportId))
					{
		// Disable multi-select cluster chosen
						document.querySelectorAll('#jform_cluster_id_chosen input, #jform_cluster_id_chosen .chosen-choices').forEach(el => {
							el.style.pointerEvents = 'none';
							el.style.opacity = '0.5';
						});

		// Disable calendar date field
						document.querySelectorAll('#jform_start_date, #jform_start_date_btn, #jform_end_date, #jform_end_date_btn').forEach(el => {
							el.disabled = true;
							el.style.opacity = '0.5';
						});

		// Disable chosen single dropdown for date range
						document.querySelectorAll('#jform_date_range_chosen a, #jform_date_range_chosen .chosen-single').forEach(el => {
							el.style.pointerEvents = 'none';
							el.style.opacity = '0.5';
						});

						jQuery('#savebtn').css({
							'pointer-events': 'none',
							'opacity': '0.6',
							'cursor': 'not-allowed'
						});


					}

				})
function closePopup() {
    jQuery("#popup-container").css('display','none');
}
			</script>