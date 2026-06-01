<?php
/**
 * @version     1.0.0
 * @package     com_tmt
 * @copyright   Copyright (C) 2023. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Techjoomla <contact@techjoomla.com> - http://techjoomla.com
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

HTMLHelper::_('jquery.token');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('behavior.multiselect');

if (JVERSION >= '4.0.0')
{
	HTMLHelper::_('bootstrap.tooltip');
}
else
{
	HTMLHelper::_('formbehavior.chosen', 'select');
	HTMLHelper::_('behavior.tooltip');
	HTMLHelper::_('bootstrap.tooltip');
}

$document = Factory::getDocument();
$document->addStylesheet(Uri::root() . 'administrator/components/com_tmt/assets/css/tmt.css');
$document->addStylesheet(Uri::root() . 'media/com_tjlms/vendors/artificiers/artficier.css');
$document->addScript(Uri::root() . 'administrator/components/com_tmt/assets/js/ajax_file_upload.js');

include_once JPATH_COMPONENT . '/js_defines.php';

?>
<div id="tmt_questions-csv" class="tjlms-wrapper row tjBs3">
	<div id="questionCsv">
		<div class="modal-header">
			<!-- <button type="button" class="close" onclick="closebackendPopup(0);" data-dismiss="modal" aria-hidden="true">×</button> -->
			<h3 id="myModalLabel"><?php echo Text::_("COM_TMT_QUESTION_CSV_IMPORT_FILE");?></h3>
		</div>


			<div class="ques-container-csv p-20">
			<div class="csv-import-question-select" >
						<div class="controls">
						<div class="fileupload fileupload-new" data-provides="fileupload">
						<div class="row">
						<div class="col-md-4">
						<label class="font-bold">
							<?php echo Text::_("COM_TMT_QUESTION_CSV_SELECT_FILE_QUIZ");?>
						</label>
						</div>
						<div class="col-md-8">
							<div class="input-append">
								<div class="uneditable-input col-md-4">
									<span class="fileupload-preview">
										<?php echo Text::_("COM_TMT_QUESTION_CSV_UPLOAD_FILE");?>
									</span>
								</div>
								<span class="btn btn-file">
									<span class="fileupload-new"><?php echo Text::_("COM_TJLMS_BROWSE");?></span>
									<input type="file" id="question-csv-upload-quiz" name="question-csv-upload-quiz"
									onchange="jQuery('.fileupload-preview').html(jQuery(this)[0].files[0].name);">
								</span>
								
								<button class="btn btn-primary ml-5" id="upload-submit"
									onclick="validate_import(document.getElementsByName('question-csv-upload-quiz'),'0','.csv-import-question-select', 'quiz-csv'); return false;">
									<span class="icon-upload icon-white"></span> <?php echo Text::_("COM_TMT_START_UPLOAD_QUIZ_CSV");?>
								</button>
								</div>
							<p class="mt-5">
							<?php
								$link_quiz_csv = '<a href="' . Uri::root() . '/components/com_tmt/sample-qa-import-quiz.csv' . '">' .
								Text::_("COM_TMT_QUESTION_CSV_SAMPLE") . '</a>';
							echo Text::sprintf('COM_TMT_CSVHELP_QUIZ', $link_quiz_csv);
							?>
							</p>
							</div>
						</div>
						<div class="clearfix"></div>
						</div>
						</div>
							<hr>
							<div class="controls">
							<div class="row fileupload fileupload-new" data-provides="fileupload">
								<div class="col-md-4">
									<label class="font-bold">
									<?php echo Text::_('COM_TMT_QUESTION_CSV_SELECT_FILE_EXE_FEED');?>
									</label>
								</div>
								<div class="col-md-8">
									<div class="input-append">
									<div class="uneditable-input span4">
									<span class="fileupload-preview-exe-feed">
										<?php echo Text::_("COM_TMT_QUESTION_CSV_UPLOAD_FILE");?>
									</span>
								</div>
								<span class="btn btn-file">
									<span class="fileupload-new"><?php echo Text::_("COM_TJLMS_BROWSE");?></span>
									<input type="file" id="question-csv-upload-exe-feed" name="question-csv-upload-exe-feed"
									onchange="jQuery('.fileupload-preview-exe-feed').html(jQuery(this)[0].files[0].name);">
								</span>
								
								<button class="btn btn-primary ml-5" id="upload-submit"
									onclick="validate_import(document.getElementsByName('question-csv-upload-exe-feed'),'0','.csv-import-question-select', 'exe-feed-csv'); return false;">
									<span class="icon-upload icon-white"></span> <?php echo Text::_("COM_TMT_START_UPLOAD_FEEDBACK_EXERCISE_CSV");?>
								</button>
								</div>
								<p class="mt-5">
									<?php
										$link_exe_feed_csv = '<a href="' . Uri::root() . '/components/com_tmt/sample-qa-import-exercise-feedback.csv' . '">' .
										Text::_("COM_TMT_QUESTION_CSV_SAMPLE") . '</a>';
									echo Text::sprintf('COM_TMT_CSVHELP_FEED_EXE', $link_exe_feed_csv);
									?>
								</p>
								</div>
							</div>
						</div>

			</div>
	</div>



	</div>
</div>

