<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
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

HTMLHelper::script('media/com_multiagency/js/user.min.js');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');
Text::script('COM_MULTIAGENCY_INTERACTION_AJAX_ERROR');

$jinput = Factory::getApplication()->input;
$view = $jinput->get('view', '', 'string');

// Set filepath for school import sample CSV
if ($view == 'schools')
{
	$filepath = Uri::root() . 'media/com_dpe/csv/SchoolSampleFormat.csv';
}
else
{
	$filepath = Uri::root() . 'media/com_dpe/csv/SchoolSampleFormat.csv';
}

$doc = Factory::getDocument();
$doc->addStyleSheet('templates/shaper_helix3/css/custom.css');
?>

<div id="dpe_school_import-csv" class="dpe-wrapper row-fluid school-import">
	<div id="import" class="form-horizontal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-header">
			<h3 id="myModalLabel" class="mb-0"><?php echo Text::_("COM_DPE_SCHOOL_CSV_UPLOAD_FILE"); ?></h3>
		</div>
		<div class="modal-body">
			<div class="control-label tjlmscenter">
				<?php echo Text::_("COM_DPE_SCHOOL_CSV_SELECT_FILE"); ?>
			</div>
			<div class="controls">
				<div class="custom-file">
					<input type="file" id="school-file-input" class="custom-file-input" name="school-csv-upload" aria-describedby="inputGroupFileAddon01">
					<label id="school-file-name" for="school-file-input" class="custom-file-label"></label>
				</div>
			</div>

			<div style="clear:both"></div>
			<div class="loader hide" id="schoolloader"></div>
			<div id="schoolInfoText"></div>
			<div>
				<a id="downloadSchoolCSVLog" target="_blank" href="#" download style="display:none">
					<?php echo Text::_("COM_DPE_DOWNLOAD_SCHOOL_LOGS"); ?>
				</a>
			</div>

			<hr class="hr hr-condensed">
			<div class="help-block center alert alert-warning">
				<?php
				$link = '<a href="' . $filepath . '">' . Text::_("COM_DPE_SCHOOL_CSV_SAMPLE") . '</a>';
				echo Text::sprintf('COM_DPE_SCHOOL_CSVHELP', $link);
				?>
			</div>

			<div class="SchoolImportBtn">
				<a href="#" class="btn btn-primary" id="dpe-school-csv-upload">
					Import
				</a>
			</div>
		</div>
	</div>
</div>

<?php
HTMLHelper::script('media/com_multiagency/assets/vendor/resumable.js');
HTMLHelper::script('media/com_multiagency/dist/app.min.js');
HTMLHelper::script('media/com_dpe/js/dpe_ucm_tab.js', ['version' => 'auto']);

Text::script('COM_DPE_SCHOOL_IMPORT_CONFIRM');
Text::script('COM_DPE_SCHOOL_IMPORT_IMPORTING');
Text::script('COM_DPE_SCHOOL_IMPORT_UPLOADING');
Text::script('COM_DPE_SCHOOL_IMPORT_SELECT_FILE');
Text::script('COM_DPE_IMPORT_SCHOOL_BTN');
Text::script('COM_DPE_SCHOOL_IMPORT_JS_ERROR');
Text::script('COM_DPE_SCHOOL_IMPORT_COLUMN_MISSING');
Text::script('COM_DPE_SCHOOL_IMPORT_TOTAL_RECORDS');
Text::script('COM_DPE_SCHOOL_IMPORT_NEW_SCHOOLS');
Text::script('COM_DPE_SCHOOL_IMPORT_NO_NEW_SCHOOLS');
Text::script('COM_DPE_SCHOOL_IMPORT_INVALID_RECORDS_LOG');
?>

<style>
input[type=file] {
	width: 90px;
	color: transparent;
}
</style>
