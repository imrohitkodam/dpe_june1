<?php
/**
 * @package     TMT
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;

$q = $displayData['item'];
$answers = $q->answers;

$canRemove = 'onclick="removeAnswerClone(this);"';

$readOnly = '';

if ($displayData['isQuestionAttempted'])
{
	$readOnly = 'readonly';
	$canRemove = 'disabled';
}

$i = 1;
JLoader::import('administrator.components.com_tmt.models.question', JPATH_SITE);
$this->model = BaseDatabaseModel::getInstance('Question', 'TmtModel');
$this->mediaLib = TJMediaStorageLocal::getInstance();
$this->answerMediaClient = 'tjlms.answer';

foreach($answers as $answer)
{
	$value = ( $answer->is_correct ) ? '1' : '0';
	$checked = ( $answer->is_correct ) ? 'checked' : '';

	$ansMediaDetails = $this->model->getMediaDetails($answer->id, $this->answerMediaClient);

	$answer->media_source        = '';
	$answer->media_id            = '';
	$answer->original_media_type = '';
	$answer->original_filename   = '';
	$answer->media_type          = '';
	$answer->media_video         = '';

	if (!empty($ansMediaDetails))
	{
		$answer->media_source = $ansMediaDetails->source;
		$answer->media_id = $ansMediaDetails->media_id;
		$answer->original_media_type = $ansMediaDetails->type;
		$answer->original_filename = $ansMediaDetails->original_filename;

		$answer->media_video = '';

		if (strpos($ansMediaDetails->type, 'video') !== false)
		{
			$answer->media_type = 'video';
			$answer->media_video = htmlspecialchars($ansMediaDetails->original_filename);
		}
		elseif(strpos($ansMediaDetails->type, 'image') !== false)
		{
			$answer->media_type = 'image';
		}
		elseif(strpos($ansMediaDetails->type, 'audio') !== false)
		{
			$answer->media_type = 'audio';
		}
		else
		{
			$answer->media_type = 'file';
		}
	}

	$mediaTypeOptions = array (
	''      => Text::_('COM_TMT_FORM_OPTION_QUESTION_MEDIA_TYPE_SELECT'),
	'image' => Text::_('COM_TMT_FORM_OPTION_QUESTION_MEDIA_TYPE_IMAGE'),
	'video' => Text::_('COM_TMT_FORM_OPTION_QUESTION_MEDIA_TYPE_VIDEO'),
	'audio' => Text::_('COM_TMT_FORM_OPTION_QUESTION_MEDIA_TYPE_AUDIO'),
	'file'  => Text::_('COM_TMT_FORM_OPTION_QUESTION_MEDIA_TYPE_FILE')
	);

	$mediaData = array();

	if (!empty($answer->media_type))
	{
		$mediaData['media']             = $answer->media_source;
		$mediaData['media_id']          = $answer->media_id;
		$mediaData['mediaUploadPath']   = $this->mediaLib->mediaUploadPath;
		$mediaData['originalMediaType'] = $answer->original_media_type;
		$mediaData['originalFilename']  = $answer->original_filename;
		$mediaData['media_type']        = $answer->media_type;
		$mediaData['media_video']       = $answer->media_video;
	}
	?>
<div class="answer-template answer-template-radio form-inline row mb-10" id="answer-template-radio<?php echo $i;?>">
	<div class="col-lg-3 pl-10">
		<label id="answer-lbl<?php echo $i;?>" for="answers_text<?php echo $i;?>" class="required answers_text" style="display:none;">
			<?php echo Text::_('COM_TMT_Q_FORM_LBL_ANSWER');?>
		</label>
		<textarea type="text" name="answers_text[]" id="answers_text<?php echo $i;?>" class="inputbox required answers_text option-value form-control" required="required" rows="5" cols="50"><?php echo $this->escape($answer->answer);?></textarea>
	</div>
	<div class="col-lg-2 answer-media-selection">
		<select data-js-id="answer-media-type" id="answer_media_type<?php echo $i;?>"
			name="answer_media_type[]" class="inputbox form-control" size="1" aria-invalid="false">
			<?php
			foreach ($mediaTypeOptions as $key => $val)
			{
				?>
				<option value="<?php echo $key; ?>" 
				<?php if (isset($mediaData['media_type'])&& $key == $mediaData['media_type']) {echo 'selected="selected"';} ?>>
				<?php echo $val; ?></option>
				<?php
			}
			?>
		</select>

		<input class="form-control" data-js-id="answer-media-image" type="file" id="answer_media_image<?php echo $i;?>"
		name="answer_media_image[]" accept="image/*" aria-invalid="false">

		<input class="form-control" data-js-id="answer-media-video" type="text" id="answer_media_video<?php echo $i;?>"
		name="answer_media_video[]" value="<?php echo isset($mediaData['media_video']) ? $mediaData['media_video'] : ''; ?>" aria-invalid="false">

		<div class="alert alert-info hide">
			<span class="icon-info" aria-hidden="true"></span>
			<?php echo Text::_('COM_TMT_MEDIA_VIDEO_SUPPORTED_NOTE'); ?>
		</div>

		<input class="" data-js-id="answer-media-audio" type="file" id="answer_media_audio<?php echo $i;?>"
		name="answer_media_audio[]" accept=".mp3,.aac,.wav,.m4a" aria-invalid="false">

		<input class="form-control" data-js-id="answer-media-file" type="file" id="answer_media_file<?php echo $i;?>"
		name="answer_media_file[]" accept=".xlsx,.xls,.doc,.docx,.ppt,.pptx,.txt,.pdf,.zip" aria-invalid="false">

		<?php
		if (!empty($mediaData['media_type']))
		{
			?>
			<div>
				<?php if ($this->isQuestionAttempted === false) { ?>
					<a class="close" onclick="tmt.tjMedia.deleteMedia('<?php echo $answer->id; ?>', 
					'<?php echo $this->answerMediaClient; ?>', '<?php echo $answer->media_id; ?>');">×</a>
				<?php } ?>
				<?php
				// Use layouts to render media elements
				$layout = new FileLayout($mediaData['media_type'], JPATH_ROOT . '/components/com_tmt/layouts/media');
				echo $layout->render($mediaData);
				?>
			</div>
			<?php
		}
		?>
	</div>
	<input type="hidden" name="answer_id_hidden[]" id="answer_id<?php echo $i;?>" value="<?php echo $answer->id;?>" />

	<input type="hidden" class="answers_iscorrect_hidden " name="answers_iscorrect_hidden[]" 
	id="answers_iscorrect_hidden<?php echo $i;?>" value="<?php echo $value;?>" />

	<div class="col-lg-1" data-js-id="mcq-correct">
		<input type="checkbox" name="answers_iscorrect[]" id="answers_iscorrect<?php echo $i;?>" class="answers_iscorrect " value="-1" 
		title="<?php echo Text::_('COM_TMT_Q_FORM_TOOLTIP_ANSWER_MARK_CORRECT');?>" <?php echo $checked;?> <?php echo $readOnly;?>/>
		<?php echo Text::_('COM_TMT_Q_FORM_BUTTON_CORRECT');?>
	</div>

	<div class="col-lg-1" data-js-type="quiz">
		<label id="answers_marks-lbl<?php echo $i;?>" for="answers_marks<?php echo $i;?>" class="required lbl_answers_marks" title="" style="display:none;">
			<?php echo Text::_('COM_TMT_Q_FORM_LBL_MARKS');?>
		</label>
		<input type="text" name="answers_marks[]" data-js-id="answers_marks" id="answers_marks<?php echo $i;?>"
		class="answers_marks inputbox required validate-whole-number form-control text-center" size="2"
		value="<?php echo $answer->marks;?>" <?php echo $readOnly;?>/>
	</div>

	<div class="col-lg-3">
		<label id="answer-comments" for="answers_comments<?php echo $i;?>" style="display:none;">
		<?php echo Text::_('COM_TMT_Q_FORM_LBL_COMMENT');?>
		</label>
		<textarea type="text" name="answers_comments[]" id="answers_comments<?php echo $i;?>" 
		class="inputbox option-value form-control" rows="5" cols="50"><?php echo $this->escape($answer->comments);?></textarea>
	</div>

	<div class="col-lg-1 text-end">
		<span class="btn btn-danger" id="remove<?php echo $i;?>" <?php echo $canRemove;?> 
		title="<?php echo Text::_('COM_TMT_Q_FORM_TOOLTIP_ANSWER_DELETE');?>"><i class="icon-trash mr-0"> </i></span>
	</div>

	<div class="col-lg-1 text-end">
		<span class="btn btn-primary sortable-handler" id="reorder<?php echo $i;?>" 
		title="<?php echo Text::_('COM_TMT_Q_FORM_TOOLTIP_ANSWER_REORDER');?>"
		style="cursor: move;"><i class="icon-move mr-0"> </i></span>
	</div>
</div>
<?php
$i++;
}
