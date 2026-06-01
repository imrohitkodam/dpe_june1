<?php
/**
 * @version    SVN: <svn_id>
 * @package    TMT
 * @author     Techjoomla <contact@techjoomla.com>
 * @copyright  Copyright (C) 2012-2013 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

JHtml::_('behavior.keepalive');
JHtml::_('behavior.tooltip');
JHtml::_('bootstrap.tooltip');
JHtml::_('jquery.token');

$document = JFactory::getDocument();
$document->addStylesheet(JUri::root() . 'administrator/components/com_tmt/assets/css/tmt.css');
$document->addStylesheet(JUri::root() . 'media/com_tjlms/vendors/artificiers/artficier.css');
$document->addScript(JUri::root() . 'administrator/components/com_tmt/assets/js/ajax_file_upload.js');

include_once JPATH_COMPONENT . '/js_defines.php';

?>
<div id="tmt_questions-csv" class="tjlms-wrapper row-fluid tjBs3">
	<div id="questionCsv" style="width:80%" class="modal hide fade form-horizontal tjlms_align_pop"  tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
		<div class="modal-header">
			<button type="button" class="close" onclick="closebackendPopup(0);" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="myModalLabel"><?php echo JText::_("COM_TMT_QUESTION_CSV_IMPORT_FILE");?></h3>
		</div>


			<div class="ques-container-csv p-20">
			<div class="csv-import-question-select" >
						<div class="controls">
						<div class="fileupload fileupload-new" data-provides="fileupload">
						<div class="row-fluid">
						<div class="span3">
						<label class="font-bold">
							<?php echo JText::_("COM_TMT_QUESTION_CSV_SELECT_FILE_QUIZ");?>
						</label>
						</div>
						<div class="span9">
							<div class="input-append">
								<div class="uneditable-input span4">
									<span class="fileupload-preview">
										<?php echo JText::_("COM_TMT_QUESTION_CSV_UPLOAD_FILE");?>
									</span>
								</div>
								<span class="btn btn-file">
									<span class="fileupload-new"><?php echo JText::_("COM_TJLMS_BROWSE");?></span>
									<input type="file" id="question-csv-upload-quiz" name="question-csv-upload"
									onchange="jQuery('.fileupload-preview').html(jQuery(this)[0].files[0].name);">
								</span>
								</div>
								<button class="btn btn-primary ml-5" id="upload-submit"
									onclick="validate_import(document.getElementById('upload-submit').
									form['question-csv-upload-quiz'],'0','.csv-import-question-select', 'quiz-csv'); return false;">
									<span class="icon-upload icon-white"></span> <?php echo JText::_("COM_TMT_START_UPLOAD_QUIZ_CSV");?>
								</button>
							<p class="mt-5">
							<?php
								$link_quiz_csv = '<a href="' . JUri::root() . '/components/com_tmt/sample-qa-import-quiz.csv' . '">' .
								JText::_("COM_TMT_QUESTION_CSV_SAMPLE") . '</a>';
							echo JText::sprintf('COM_TMT_CSVHELP_QUIZ', $link_quiz_csv);
							?>
							</p>
							</div>
						</div>
						<div class="clearfix"></div>
						</div>
						</div>
							<hr>
							<div class="controls">
							<div class="row-fluid fileupload fileupload-new" data-provides="fileupload">
								<div class="span3">
									<label class="font-bold">
									<?php echo JText::_('COM_TMT_QUESTION_CSV_SELECT_FILE_EXE_FEED');?>
									</label>
								</div>
								<div class="span9">
									<div class="input-append">
									<div class="uneditable-input span4">
									<span class="fileupload-preview-exe-feed">
										<?php echo JText::_("COM_TMT_QUESTION_CSV_UPLOAD_FILE");?>
									</span>
								</div>
								<span class="btn btn-file">
									<span class="fileupload-new"><?php echo JText::_("COM_TJLMS_BROWSE");?></span>
									<input type="file" id="question-csv-upload-exe-feed" name="question-csv-upload"
									onchange="jQuery('.fileupload-preview-exe-feed').html(jQuery(this)[0].files[0].name);">
								</span>
								</div>
								<button class="btn btn-primary ml-5" id="upload-submit"
									onclick="validate_import(document.getElementById('upload-submit').
									form['question-csv-upload-exe-feed'],'0','.csv-import-question-select', 'exe-feed-csv'); return false;">
									<span class="icon-upload icon-white"></span> <?php echo JText::_("COM_TMT_START_UPLOAD_FEEDBACK_EXERCISE_CSV");?>
								</button>
								<p class="mt-5">
									<?php
										$link_exe_feed_csv = '<a href="' . JUri::root() . '/components/com_tmt/sample-qa-import-exercise-feedback.csv' . '">' .
										JText::_("COM_TMT_QUESTION_CSV_SAMPLE") . '</a>';
									echo JText::sprintf('COM_TMT_CSVHELP_FEED_EXE', $link_exe_feed_csv);
									?>
								</p>
								</div>
							</div>
						</div>

			</div>
	</div>



	</div>
</div>

