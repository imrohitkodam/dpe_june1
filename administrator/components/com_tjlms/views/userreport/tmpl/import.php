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
JHtml::script(Uri::root().'administrator/components/com_tjlms/assets/js/tjlms_admin.js');
JHtml::script(Uri::root().'administrator/components/com_tjlms/assets/js/ajax_file_upload.js');

$filepath = Uri::root() . 'administrator/components/com_tjlms/csv/userData.csv';
?>
<div id="tjlms_import-csv" class="tjlms-wrapper row-fluid">
	<div id="import" style="width:80%" class="modal d-none fade form-horizontal"  tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="myModalLabel"><?php echo Text::_("COM_TJLMS_ENROLLMENT_CSV_UPLOAD_FILE");?></h3>
		</div>
		<div class="control-group" >
				<div class="control-label"><?php echo Text::_("COM_TJLMS_ENROLLMENT_CSV_SELECT_FILE");?></div>
				<div class="controls">
					<div class="fileupload fileupload-new pull-left" data-provides="fileupload">
						<div class="input-append">
							<div class="uneditable-input span4">
								<span class="fileupload-preview">
									<?php echo Text::_("COM_TJLMS_ENROLLMENT_CSV_IMPORT_FILE");?>
								</span>
							</div>
							<span class="btn btn-file">
								<span class="fileupload-new"><?php echo Text::_("COM_TJLMS_BROWSE");?></span>
								<input type="file" id="user-csv-upload" name="question-csv-upload" onchange="validate_file(this,'','userImport')">
							</span>
						</div>
					</div>
					<div style="clear:both"></div>
				</div>
			</div>
			<hr class="hr hr-condensed">
			<div class="help-block center">
				<?php
					$link = '<a href="' . $filepath . '">' . Text::_("COM_TJLMS_ENROLLMENT_CSV_SAMPLE") . '</a>';
				echo Text::sprintf('COM_TJLMS_ENROLLMENT_CSVHELP', $link);
				?>
			</div>
			<hr class="hr hr-condensed">
		</div>
	</div>

<?php

