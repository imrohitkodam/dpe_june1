<?php
/**
 * @package     Shika
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$mediaTypeOptions = array (
						'' => Text::_('COM_TMT_FORM_OPTION_QUESTION_MEDIA_TYPE_SELECT'),
						'image' => Text::_('COM_TMT_FORM_OPTION_QUESTION_MEDIA_TYPE_IMAGE'),
						'video' => Text::_('COM_TMT_FORM_OPTION_QUESTION_MEDIA_TYPE_VIDEO'),
						'audio' => Text::_('COM_TMT_FORM_OPTION_QUESTION_MEDIA_TYPE_AUDIO'),
						'file' => Text::_('COM_TMT_FORM_OPTION_QUESTION_MEDIA_TYPE_FILE')
					);
?>

<div class="answer-template answer-template-radio form-inline mb-10 row" id="answer-template-radio">
	<div class="col-md-3 pl-10">
		<label id="answer-lbl" for="answers_text" class="required answers_text" style="display:none;">
			<?php echo Text::_('COM_TMT_Q_FORM_LBL_ANSWER');?>
		</label>
		<textarea type="text" name="answers_text[]" id="answers_text" class="inputbox form-control required answers_text option-value" required="required" rows="5" cols="50"></textarea>
	</div>

	<div class="col-md-2 answer-media-selection">
		<select data-js-id="answer-media-type" id="answer_media_type"
			name="answer_media_type[]" class="inputbox form-control" size="1" aria-invalid="false">
			<?php
			foreach ($mediaTypeOptions as $key => $value)
			{
				?>
				<option value="<?php echo $key; ?>"><?php echo $value; ?></option>
				<?php
			}
			?>
		</select>

		<input class="d-none form-control" data-js-id="answer-media-image" type="file" id="answer_media_image"
		name="answer_media_image[]" accept="image/*" aria-invalid="false">

		<div class="alert-info image-note d-none">
			<span class="icon-info" aria-hidden="true"></span>
			<?php echo Text::_('COM_TMT_MEDIA_IMAGE_SUPPORTED_NOTE'); ?>
		</div>

		<input class="d-none" data-js-id="answer-media-video" type="text" id="answer_media_video"
		name="answer_media_video[]" value="" aria-invalid="false">

		<div class="alert alert-info d-none video-note">
			<span class="icon-info" aria-hidden="true"></span>
			<?php echo Text::_('COM_TMT_MEDIA_VIDEO_SUPPORTED_NOTE'); ?>
		</div>

		<input class="d-none" data-js-id="answer-media-audio" type="file" id="answer_media_audio"
		name="answer_media_audio[]" accept=".mp3,.aac,.wav,.m4a" aria-invalid="false">
		<div class="alert alert-info audio-note d-none">
			<span class="icon-info" aria-hidden="true"></span>
			<?php echo Text::_('COM_TMT_MEDIA_AUDIO_SUPPORTED_NOTE'); ?>
		</div>

		<input class="d-none" data-js-id="answer-media-file" type="file" id="answer_media_file"
		name="answer_media_file[]" accept=".xlsx,.xls,.doc,.docx,.ppt,.pptx,.txt,.pdf,.zip" aria-invalid="false">
		<div class="alert alert-info file-note d-none">
			<span class="icon-info" aria-hidden="true"></span>
			<?php echo Text::_('COM_TMT_MEDIA_FILE_SUPPORTED_NOTE'); ?>
		</div>
	</div>

	<input type="hidden" name="answer_id_hidden[]" id="answer_id" value="0" />
	<input type="hidden" name="answers_iscorrect_hidden[]" id="answers_iscorrect_hidden" class="answers_iscorrect_hidden" value="0" />

	<div class="col-lg-1" data-js-id="mcq-correct">
		<input type="checkbox"  name="answers_iscorrect[]" id="answers_iscorrect" value="-1" class="answers_iscorrect valign-text-bottom" title="<?php echo Text::_('COM_TMT_Q_FORM_TOOLTIP_ANSWER_MARK_CORRECT');?>"/>
		<?php echo Text::_('COM_TMT_Q_FORM_BUTTON_CORRECT');?>
	</div>

	<div class="col-lg-1" data-js-type="quiz">
		<label id="answers_marks-lbl" for="answers_marks" class="required lbl_answers_marks" title="" style="display:none;">
			<?php echo Text::_('COM_TMT_Q_FORM_LBL_MARKS');?>
		</label>
		<input type="text" name="answers_marks[]" data-js-id="answers_marks" id="answers_marks" class="answers_marks form-control inputbox required validate-whole-number text-center" size="2" value="0" style=""/>
	</div>

	<div class="col-lg-3">
		<textarea type="text" name="answers_comments[]" id="answers_comments" class="inputbox form-control option-value" rows="5" cols="50" ></textarea>
	</div>

	<div class="col-lg-1 text-end text-end">
		<span class="btn btn-danger" id="remove" onclick="removeAnswerClone(this);" title="<?php echo Text::_('COM_TMT_Q_FORM_TOOLTIP_ANSWER_DELETE');?>"><i class="icon-trash mr-0"> </i></span>
	</div>

	<div class="col-lg-1 text-end">
		<span class="btn btn-primary sortable-handler" id="reorder" title="<?php echo Text::_('COM_TMT_Q_FORM_TOOLTIP_ANSWER_REORDER');?>"style="cursor: move;"><i class="icon-move mr-0"> </i></span>
	</div>
</div>


<div class="answer-template answer-template-checkbox form-inline mb-10 row" id="answer-template-checkbox">
	<div class="col-lg-3 pl-10">
		<label id="answer-lbl" for="answers_text" class="required answers_text" style="display:none;">
			<?php echo Text::_('COM_TMT_Q_FORM_LBL_ANSWER');?>
		</label>
		<textarea type="text" name="answers_text[]" id="answers_text" class="inputbox form-control required answers_text" rows="5" cols="50"></textarea>
	</div>

	<div class="col-lg-2 answer-media-selection">
		<select data-js-id="answer-media-type" id="answer_media_type"
			name="answer_media_type[]" class="inputbox form-control" size="1" aria-invalid="false">
			<?php
			foreach ($mediaTypeOptions as $key => $value)
			{
				?>
				<option value="<?php echo $key; ?>"><?php echo $value; ?></option>
				<?php
			}
			?>
		</select>

		<input class="d-none form-control" data-js-id="answer-media-image" type="file" id="answer_media_image"
		name="answer_media_image[]" accept="image/*" aria-invalid="false">

		<div class="alert alert-info image-note d-none">
			<span class="icon-info" aria-hidden="true"></span>
			<?php echo Text::_('COM_TMT_MEDIA_IMAGE_SUPPORTED_NOTE'); ?>
		</div>

		<input class="d-none form-control" data-js-id="answer-media-video" type="text" id="answer_media_video"
		name="answer_media_video[]" value="" aria-invalid="false">

		<div class="alert alert-info video-note d-none">
			<span class="icon-info" aria-hidden="true"></span>
			<?php echo Text::_('COM_TMT_MEDIA_VIDEO_SUPPORTED_NOTE'); ?>
		</div>

		<input class="d-none" data-js-id="answer-media-audio" type="file" id="answer_media_audio"
		name="answer_media_audio[]" accept=".mp3,.aac,.wav,.m4a" aria-invalid="false">

		<div class="alert alert-info audio-note d-none">
			<span class="icon-info" aria-hidden="true"></span>
			<?php echo Text::_('COM_TMT_MEDIA_AUDIO_SUPPORTED_NOTE'); ?>
		</div>

		<input class="d-none form-control" data-js-id="answer-media-file" type="file" id="answer_media_file"
		name="answer_media_file[]" accept=".xlsx,.xls,.doc,.docx,.ppt,.pptx,.txt,.pdf,.zip" aria-invalid="false">

		<div class="alert alert-info file-note d-none">
			<span class="icon-info" aria-hidden="true"></span>
			<?php echo Text::_('COM_TMT_MEDIA_FILE_SUPPORTED_NOTE'); ?>
		</div>
	</div>

	<input type="hidden" name="answer_id_hidden[]" id="answer_id" value="0" />

	<input type="hidden" name="answers_iscorrect_hidden[]" id="answers_iscorrect_hidden" class=" form-control answers_iscorrect_hidden" value="0" />


	<div class="col-lg-1" data-js-id="mcq-correct">
		<input type="checkbox" name="answers_iscorrect[]" id="answers_iscorrect" class=" answers_iscorrect valign-text-bottom" value="-1"  title="<?php echo Text::_('COM_TMT_Q_FORM_TOOLTIP_ANSWER_MARK_CORRECT');?>"/>
	<?php echo Text::_('COM_TMT_Q_FORM_BUTTON_CORRECT');?>
	</div>

	<div class="col-lg-1" data-js-type="quiz">
		<label id="answers_marks-lbl" for="answers_marks" class="required lbl_answers_marks" title="" style="display:none;">
				<?php echo Text::_('COM_TMT_Q_FORM_LBL_MARKS');?>
			</label>
		<input type="text" name="answers_marks[]" data-js-id="answers_marks"  id="answers_marks" class="form-control answers_marks inputbox required validate-whole-number text-center" size="2" value="0" style=""/>
	</div>

	<div class="col-lg-3">
		<textarea type="text" name="answers_comments[]" id="answers_comments" class="form-control inputbox" rows="5" cols="50"></textarea>
	</div>

	<div class="col-lg-1 text-end">
		<span class="btn btn-danger" id="remove" onclick="removeAnswerClone(this);" title="<?php echo Text::_('COM_TMT_Q_FORM_TOOLTIP_ANSWER_DELETE');?>"><i class="icon-trash"> </i></span>
	</div>

	<div class="col-lg-1 text-end">
		<span class="btn btn-primary sortable-handler" id="reorder" title="<?php echo Text::_('COM_TMT_Q_FORM_TOOLTIP_ANSWER_REORDER');?>"style="cursor: move;"><i class="icon-move"> </i></span>
	</div>
</div>

<div class="answer-template-text form-inline clearfix" id="answer-template-text">
	<div class="control-group" data-js-id="textinput-input">
		<div class="control-label" title="<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_TEXT_LABEL');?>">
			<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_TEXT_LABEL');?>
		</div>
		<div class="controls">
			<input type="hidden" name="answer_id_hidden[]" id="answer_id" value="0" />
			<input type="text" name="answers_text[]" id="answers_text" class="inputbox answers_text" size="20" value="" />
		</div>
	</div>
	<div class="alert alert-info" data-js-id="textinput-messsage">
		<?php echo Text::_("COM_TMT_QUESTION_TYPE_TEXT_FEEDBACK_MSG"); ?>
	</div>
</div>

<div class="answer-template-objtext form-inline clearfix" id="answer-template-objtext">
	<div class="control-group" data-js-id="textinput-input">
		<label for="answers_text" class="control-label required" title="<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_OBJTEXT_LABEL');?>">
			<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_OBJTEXT_LABEL');?>
		</label>
		<div class="controls">
			<input type="hidden" name="answer_id_hidden[]" id="answer_id" value="0" />
			<input type="text" name="answers_text[]" id="answers_text" class="inputbox required answers_text" size="20" value=""/>
			<div class="pt-10">
				<em >
				<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_TEXT_NOTE');?>
				</em>
			</div>
		</div>
	</div>
	<div class="alert alert-info" data-js-id="textinput-messsage">
		<?php echo Text::_("COM_TMT_QUESTION_TYPE_TEXT_FEEDBACK_MSG"); ?>
	</div>
</div>

<div class="answer-template-textarea form-inline clearfix" id="answer-template-textarea">
	<div class="control-group" data-js-id="textinput-input">
		<div class="control-label" title="<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_TEXT_LABEL');?>">
			<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_TEXT_LABEL');?>
		</div>
		<div class="controls">
			<input type="hidden" name="answer_id_hidden[]" id="answer_id" value="0" />

			<textarea type="text" name="answers_text[]" id="answers_text" class="inputbox answers_text col-lg-4" rows="5" cols="50"></textarea>
		</div>
	</div>
	<div class="alert alert-info" data-js-id="textinput-messsage">
		<?php echo Text::_("COM_TMT_QUESTION_TYPE_TEXT_FEEDBACK_MSG"); ?>
	</div>
	<div class="question-params-textarea form-inline clearfix" id="question-params-textarea">
		<div class="control-group">
			<div class="control-label" title="<?php echo Text::_('COM_TMT_Q_FORM_PARAMS_TEXTAREA_MINLENGTH'); ?>">
				<?php echo Text::_('COM_TMT_Q_FORM_PARAMS_TEXTAREA_MINLENGTH');?>
			</div>
			<div class="controls">
			<input type="text" name="jform[params][minlength]" class="inputbox question_params" size="20"
			value="" data-js-id="answers_min_length" />
			</div>
		</div>
		<div class="control-group">
			<div class="control-label" title="<?php echo Text::_('COM_TMT_Q_FORM_PARAMS_TEXTAREA_MAXLENGTH'); ?>">
				<?php echo Text::_('COM_TMT_Q_FORM_PARAMS_TEXTAREA_MAXLENGTH');?>
			</div>
			<div class="controls">
			<input type="text" name="jform[params][maxlength]" class="inputbox question_params" size="20"
			value="" data-js-id="answers_max_length" />
			</div>
		</div>
	</div>
</div>

<div class="answer-template-file_upload form-inline clearfix" id="answer-template-file_upload">
	<div class="alert alert-info">
		<?php echo Text::_("COM_TMT_QUESTION_FILE_UPLOAD_MSG"); ?>
	</div>

	<div class="question-params-file_upload form-inline clearfix">
	<div class="control-group">
		<div class="control-label" title="<?php echo Text::_('COM_TMT_Q_FORM_PARAMS_FILE_FORMAT_LABEL'); ?>">
			<?php echo Text::_('COM_TMT_Q_FORM_PARAMS_FILE_FORMAT_LABEL');?>
		</div>
		<div class="controls">
		<input type="text" name="jform[params][file_format]" class="inputbox question_params" size="20"
		value="" placeholder="e.g. pdf,doc,png"/>
		</div>
	</div>

	<div class="control-group">
		<div class="control-label" title="<?php echo Text::_('COM_TMT_Q_FORM_PARAMS_FILE_COUNT_LABEL'); ?>">
			<?php echo Text::_('COM_TMT_Q_FORM_PARAMS_FILE_COUNT_LABEL');?>
		</div>
		<div class="controls">
			<input type="number" name="jform[params][file_count]" id="question_params"
			class="inputbox question_params" size="20" value=""/>
		</div>
	</div>

	<div class="control-group">
		<div class="control-label" title="<?php echo Text::_('COM_TMT_Q_FORM_PARAMS_FILE_SIZE_LABEL');?>">
			<?php echo Text::_('COM_TMT_Q_FORM_PARAMS_FILE_SIZE_LABEL');?>
		</div>
		<div class="controls">
			<input type="number" name="jform[params][file_size]" class="inputbox question_params" size="20" value=""/>
		</div>
	</div>
</div>
</div>

<div class="answer-template-rating form-inline row" id="answer-template-rating">
	<div class="control-group">
		<div class="col-lg-6">
			<div class="control-label" title="<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_TEXT_LABEL');?>">
			<label id="answers_lower_text-lbl" for="answers_lower_text" class="required lbl_answers_lower_text" title="">
				<?php echo Text::_('COM_TMT_Q_FORM_LOWER_RATING_LABEL');?>
			</label>
			</div>
			<div class="controls">
				<input type="hidden" name="answer_id_hidden[]" id="answer_id" value="0" />
				<input type="text" name="answers_text[]" id="answers_lower_text" class="inputbox answers_text col-lg-2 lower_range required validate-numeric" size="20" value="" data-js-id="answers_lower_text"/>
			</div>
		</div>
		<div class="col-lg-6">
			<div class="control-label" title="<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_TEXT_LABEL');?>">
				<label id="answers_upper_text-lbl" for="answers_upper_text" class="required lbl_answers_upper_text" title="">
					<?php echo Text::_('COM_TMT_Q_FORM_UPPER_RATING_LABEL');?>
				</label>
			</div>
			<div class="controls">
				<input type="hidden" name="answer_id_hidden[]" id="answer_id" value="0" />
				<input type="text" name="answers_text[]" id="answers_upper_text" class="inputbox answers_text col-lg-2 upper_range required validate-numeric" size="20" value="" data-js-id="answers_upper_text"/>
			</div>
		</div>
	</div>
	<div>
		<?php  foreach ($this->form->getGroup('params') as $field) : ?>
				<?php echo $field->renderField(); ?>
		<?php endforeach; ?>
	</div>
</div>

