<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::stylesheet('media/techjoomla_strapper/bs3/css/bootstrap.css');

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('bootstrap.renderModal', 'a.modal');
HTMLHelper::_('jquery.token');

$options['relative'] = true;
$attribs['defer'] = 'defer';
HTMLHelper::script('com_tjlms/js/tjlmsAdmin.min.js', $options, $attribs);
$app = Factory::getApplication();
$courseId = $app->input->get('course_id', 0, 'INT');

include_once JPATH_COMPONENT . '/js_defines.php';
HTMLHelper::script(Uri::root() . 'administrator/components/com_tmt/assets/js/ajax_file_upload.js');

$courseDataPath = Uri::root() . 'administrator/components/com_tjlms/csv/courseCompletioData.csv';
$lessonDataPath = Uri::root() . 'administrator/components/com_tjlms/csv/lessonCompletionData.csv';

?>
<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?>">
	<form action="<?php echo Route::_('index.php?option=com_tjlms&view=tools'); ?>" method="post" name="adminForm" id="adminForm">
	<?php
		ob_start();
		include JPATH_BASE . '/components/com_tjlms/layouts/header.sidebar.php';
		$layoutOutput = ob_get_contents();
		ob_end_clean();
		echo $layoutOutput;
	?> <!--// JHtmlsidebar for menu ends-->
	<div class="progressbar"></div>
	<div class="row">
		<div class="panel panel-primary panel-heading col-md-6">
			<div class="">
				<h4><?php echo Text::_('COM_TJLMS_TITLE_RECALCULATE_PROGRESS_SELECTED_COURSE'); ?></h4>
			</div>
			<div class="row">
				<div class="control-group">
					<div class="controls">
						<label>
							<?php echo Text::_('COM_TJLMS_SELECT_COURSE'); ?>
						</label>
						<?php echo HTMLHelper::_('select.genericlist', $this->courses, 'filter[course_names]', 'class="course_names form-select" onchange="tjlmsAdmin.tools.showEnrolledUsers(this.value)"', 'value', 'text', $courseId ? $courseId : $this->state->get('filter.course_names'), 'filter_course_names'); ?>
					</div>
				</div>
			</div>
			<div>
			<div class="alert-info hide" id="enrolled_user_notice">
			</div>
			<!--//DPE HACK - Can go in Shika core MR link : https://git.tekdi.net/tekdi/dpe/-/merge_requests/3064-->
			<div id='enrolledusercheckbox'> 
				<input type="checkbox" name="select_enrolled_user" id="select_enrolled_user" value="">
				 <label for="select_enrolled_user"> <?php echo Text::_('COM_TJLMS_TOOLS_SPECIFIC_USER');?></label>
			</div>
			<div class="alert-info hide" id="enrolluserlist">
				<select id="enrolledUser" name="Select enrolled user" class="course_names form-select">   
				</select>

			</div>
			<!--DPE HACK END-->
			<br>
			<button type="button" class="btn btn btn-success inactiveLink disabled" id="recalculate" onclick="tjlmsAdmin.tools.calculateCourseProgress();"><?php echo Text::_('COM_TJLMS_PROCEED'); ?></button>
			</div>
		</div>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
	<div class="panel panel-primary panel-heading col-md-6">
		<div class="control-group csv-import-historical-data" >
			<h4><?php echo Text::_("COM_TJLMS_TOOLS_HISTORICAL_DATA_CSV_SELECT_FILE");?></h4>
				<div class="controls-group">
					<div class="fileupload fileupload-new pull-left" data-provides="fileupload">
						<div class="input-append">
							<div class="uneditable-input col-md-4">
								<span class="fileupload-preview">
									<?php echo Text::_("COM_TJLMS_TOOLS_HISTORICAL_DATA_CSV_IMPORT_FILE");?>
								</span>
							</div>
							<span class="btn btn-file">
								<span class="fileupload-new"><?php echo Text::_("COM_TJLMS_CHOOSE_FILE");?></span>
								<input type="file" id="historical-csv-upload" name="historical-csv-upload"
								onchange="jQuery('.fileupload-preview').html(jQuery(this)[0].files[0].name);">
							</span>
							<button class="btn btn-primary" id="upload-submit"
								onclick="validate_import(document.getElementById('upload-submit').form['historical-csv-upload'],'historicalData','.csv-import-historical-data'); return false;">
								<span class="icon-upload icon-white"></span> <?php echo Text::_("COM_TJLMS_START_UPLOAD");?>
							</button>
						</div>
					</div>
					<div style="clear:both"></div>
				</div>
			</div>
			<hr class="hr hr-condensed">
			<div class="help-block">
				<?php
					echo Text::sprintf('COM_TJLMS_TOOLS_HISTORICAL_DATA_CSVHELP');
				?>
				<br>
				<br>
				<div class="row">
					<div class="col-md-2"></div>
					<div class="col-md-8">
						<?php
							$link = '<a href="' . $courseDataPath . '">' . Text::_("COM_TJLMS_TOOLS_HISTORICAL_DATA_CSV_SAMPLE") . '</a>';
							echo Text::sprintf('COM_TJLMS_TOOLS_HISTORICAL_DATA_COURSE_COMPLETION', $link);
						?>
					<br>
					<?php
						$link = '<a href="' . $lessonDataPath . '">' . Text::_("COM_TJLMS_TOOLS_HISTORICAL_DATA_CSV_SAMPLE") . '</a>';
						echo Text::sprintf('COM_TJLMS_TOOLS_HISTORICAL_DATA_LESSON_COMPLETION', $link);
					?>
					</div>
					<div class="col-md-2"></div>
				</div>
			</div>
			<hr class="hr hr-condensed">
		</div>
	</div>

</div> <!--techjoomla-bootstrap-->
<!--//DPE HACK - Can go in Shika core MR link : https://git.tekdi.net/tekdi/dpe/-/merge_requests/3064-->
<script>
	tjlmsAdmin.tools.init();
	jQuery(document).ready(function(){

		jQuery("#enrolluserlist").hide();
		jQuery("#enrolledusercheckbox").hide();
		jQuery("#select_enrolled_user").change(function() {
        jQuery('#enrolluserlist').toggle(this.checked);
        jQuery("#enrolledUser").show();
    });
	})
</script>
