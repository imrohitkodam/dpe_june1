<?php
/**
 * @version     1.0.0
 * @package     com_tjlms
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      TechJoomla <extensions@techjoomla.com> - http://www.techjoomla.com
 */

// no direct access
defined('_JEXEC') or die;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
include_once JPATH_COMPONENT.'/js_defines.php';
JHtml::script(Uri::root().'administrator/components/com_tmt/assets/js/ajax_file_upload.js');

$filepath = Uri::root() . 'administrator/components/com_tjlms/csv/userData.csv';
$timezoneFilepath = Uri::root() . 'administrator/components/com_tjlms/csv/timeZone.csv';
$courseList = Uri::root() . 'administrator/index.php?option=com_tjlms&view=courses';
$groupList = Uri::root() . 'administrator/index.php?option=com_users&view=groups';

?>
<div id="tjlms_import-csv" class="tjlms-wrapper ">
	<div id="container-fluid">
	<div id="import">
		<div class="modal-header">
			<h3 id="myModalLabel"><?php echo Text::_("COM_TJLMS_ENROLLMENT_CSV_UPLOAD_FILE");?></h3>
		</div>
		<div class=" csv-import-user-select" >
			<div class="row">
		<div class="form-label text-center col-md-2">
			<input id="notify_user_import" type="checkbox" name="notify_user_import" checked="checked">
						<?php echo Text::_('COM_TJLMS_NOTIFY_ASSIGN_USER'); ?>
		</div>
		
				<div class="form-label  col-md-2"><?php echo Text::_("COM_TJLMS_ENROLLMENT_CSV_SELECT_FILE");?></div>
				<div class="controls col-md-8">
					<div class="fileupload fileupload-new pull-left" data-provides="fileupload">
						<div class="input-append csv-border">
							<div class="uneditable-input ">
								<span class="fileupload-preview">
									<?php echo Text::_("COM_TJLMS_ENROLLMENT_CSV_IMPORT_FILE");?>
								</span>
							</div>
							<span class="btn btn-file">
								<span class="fileupload-new"><?php echo Text::_("COM_TJLMS_CHOOSE_FILE");?></span>
								<input type="file" id="user-csv-upload" name="question-csv-upload"
								onchange="jQuery('.fileupload-preview').html(jQuery(this)[0].files[0].name);">
							</span>
							<button class="btn btn-primary" id="upload-submit"
								onclick="validate_import(document.getElementsByName('question-csv-upload'),'1','.csv-import-user-select'); return false;">
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
					echo Text::sprintf('COM_TJLMS_ENROLLMENT_CSVHELP');
				?>
				<br>
				<div class="row-fluid">
					<div class="span4"></div>
					<div class="span4">
						<?php
							$link = '<a href="' . $filepath . '">' . Text::_("COM_TJLMS_ENROLLMENT_CSV_SAMPLE") . '</a>';
							echo Text::sprintf('COM_TJLMS_ENROLLMENT_CSVHELP1', $link);
						?>
					<br>
					<?php
						$link = '<a href="' . $timezoneFilepath . '">' . Text::_("COM_TJLMS_ENROLLMENT_CSV_SAMPLE") . '</a>';
						echo Text::sprintf('COM_TJLMS_TIMEZONE_CSVHELP', $link);
					?>
					<br>
					<?php
						$link = '<a target="_blank" href="' . $courseList . '">' . Text::_("COM_TJLMS_ENROLLMENT_CSV_VIEW_LIST") . '</a>';
						echo Text::sprintf('COM_TJLMS_ENROLLMENT_CSVHELP2', $link);
					?>
					<br>
					<?php
						$link = '<a target="_blank" href="' . $groupList . '">' . Text::_("COM_TJLMS_ENROLLMENT_CSV_VIEW_LIST") . '</a>';
						echo Text::sprintf('COM_TJLMS_ENROLLMENT_CSVHELP3', $link);
					?>
					</div>
					<div class="span4"></div>
				</div>
			</div>
			<hr class="hr hr-condensed">
	</div>
</div>
</div>