<?php
/**
 * @package     Multiagency
 * @subpackage  com_multiagency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// no direct access
defined('_JEXEC') or die;
use Joomla\CMS\Uri\Uri;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormHelper;

HTMLHelper::script('media/com_multiagency/js/user.min.js');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

Text::script('COM_MULTIAGENCY_INTERACTION_AJAX_ERROR');

$jinput = Factory::getApplication()->input;
$view = $jinput->get('view', '', 'string');

if ($view == 'users')
{
	$filepath = Uri::root() . 'media/com_multiagency/csv/SampleFormat.csv';
}
else
{
	$filepath = Uri::root() . 'media/com_multiagency/csv/SampleFormat.csv';
}



$doc = Factory::getDocument();
$doc->addStyleSheet('templates/shaper_helix3/css/custom.css');

/*
FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_multiagency/models/fields/');
$agencies = FormHelper::loadFieldType('agency', false);
$agencyList = $agencies->getOptionsExternally();
*/

// DPE Hack to get cluster options instaed of agencies
FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields');
$clusterList = FormHelper::loadFieldType('Cluster', false);
$clusterOptions = $clusterList->getOptionsExternally();
// DPE Hack end
?>

<div id="tjlms_import-csv" class="tjlms-wrapper row-fluid staff-import">
	<div id="import" class="form-horizontal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-header">
			<h3 id="myModalLabel" class="mb-0"><?php echo Text::_("COM_MULTIAGENCY_ENROLLMENT_CSV_UPLOAD_FILE"); ?></h3>
		</div>
		<div class="modal-body">
			<div class="control-group csv-import-user-select">
				<div class="control-label mb-10 staff_notify_user_import">
					<input id="notify_user_import" type="checkbox" name="notify_user_import" checked="checked">
					<?php echo Text::_('COM_MULTIAGENCY_NOTIFY_ASSIGN_USER'); ?>
				</div>
				<div class="control-group tjlmscenter">
					<select name="client_id" class="inputbox selectAgency" id="agency">
						<!-- For DPE used cluster options here instead of agencyList -->
						<?php echo HTMLHelper::_('select.options', $clusterOptions, 'value', 'text', ''); ?>
					</select>
				</div>
				<div class="" id="license_error">
				</div>
			</div>
			<!-- <div class="userimportcheckbox controls float-end col-md-7 help-block center alert alert-warning">
				
				<label class="float-end">
					<input type="checkbox" name="phishingConsent" id="phishingConsent">
					<?php echo Text::_('COM_DPE_CHECK_PHISNG_USER_CONSENT');?>
				</label>

			</div> -->

			<div class="control-label tjlmscenter tjlmscenterlabel"><?php echo Text::_("COM_MULTIAGENCY_ENROLLMENT_CSV_SELECT_FILE"); ?></div>
			<div class="controls">
				<div class="custom-file">
					<input type="file" id="file-input" class="custom-file-input" name="question-csv-upload" aria-describedby="inputGroupFileAddon01">
					<label id="file-name" for="file-input" class="custom-file-label"></label> <!-- Updated -->
				</div>

			</div>
			
			<div style="clear:both"></div>
			<div class="loader hide" id="userloader"></div>
			<div id="infoText"></div>
			<div>
				<a id="downloadCSVLog" target="_blank" href="#" download style="display:none">
					<?php echo Text::_("COM_MULTIAGENCY_DOWNLOAD_LOGS"); ?>
				</a>
			</div>

			<hr class="hr hr-condensed">
			<div class="help-block center alert alert-warning">
				<?php
				$link = '<a href="' . $filepath . '">' . Text::_("COM_MULTIAGENCY_ENROLLMENT_CSV_SAMPLE") . '</a>';
				echo Text::sprintf('COM_MULTIAGENCY_ENROLLMENT_CSVHELP', $link);
				?>
			</div>

			<div class="UserImportBtn">
				<a href="#" class="btn btn-primary" id="tjlms-csv-uploads">
					<?php echo Text::_("COM_DPE_IMPORT_USER_BTN"); ?>
				</a>
			</div>

		</div>
	</div>
</div>

<?php
HTMLHelper::script('media/com_multiagency/assets/vendor/resumable.js');
HTMLHelper::script('media/com_multiagency/dist/app.min.js');
?>
<style>
	input[type=file] {
		width: 90px;
		color: transparent;
	}
</style>
<script type="text/javascript">
	jQuery(document).ready(function() {
    var elementId = "file-input"; // ID of the file input element
    var userImport = 1;
    var importObj = new multiAgency.UI.Import(elementId, userImport);  

    jQuery("#tjlms-csv-uploads").click(function(e) {
    e.preventDefault();  // Prevent form submission
    var n = jQuery(".selectAgency option:selected").text();
    var r = jQuery(".selectAgency option:selected").val();
    var filename = jQuery('#file-name').text();
    if(filename)
    {
    	   if (window.confirm(importObj.sprintf(Joomla.Text._("COM_MULTIAGENCY_IMPORT_STAFF_CONFIRM"), n)) == 1) {
    	 // Display upload start message
    	 jQuery("#infoText").html("")
    	 .addClass("alert alert-warning")
    	 .html(Joomla.JText._("COM_MULTIAGENCY_CSV_USER_IMPORTED"))
    	 .show();

    	 jQuery('#userloader').show();
    // Start the resumable upload
    importObj.getResumable().upload();

} else {
    // If the user did not confirm, reset the form or process
    importObj.reset();
}
    }
 


});
    jQuery('#agency_chosen').css('width','300px');
});

</script>
