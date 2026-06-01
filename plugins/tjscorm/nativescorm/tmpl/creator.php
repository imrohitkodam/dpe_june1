<?php
/**
 * @package Tjlms
 * @copyright Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     http://www.com
 */
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Utility\Utility;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

$lang_con_for_upload_formt_file = "PLG_TJSCORM_NATIVESCORM_UPLOAD_NEW_FORMAT";
$passing_score = '';
$grade_method = '' ;

$cMax    = (int) $comp_params->get('lesson_upload_size', '0', 'INT');
if(method_exists('Utility', 'getMaxUploadSize'))
{
	$cMax = Utility::getMaxUploadSize($cMax . 'MB');
}
$allowedFileSize = HTMLHelper::_('number.bytes', $cMax);

$filename = "<div class='help-block'>" . Text::sprintf('COM_TJLMS_UPLOAD_FILE_WITH_EXTENSION','zip', $allowedFileSize) . "</div>";
$file_browse_lang = "PLG_TJSCORM_NATIVESCORM_BROWSE";
$edit = 0;
$subformat = '';

if (!empty($lesson->sub_format))
{
	$subformat = $lesson->sub_format;
	$subformat_source_options = explode('.', $subformat);
	$source_plugin = $subformat_source_options[0];
	$source_option = $subformat_source_options[1];

	if (!empty($source_option) && $source_plugin == 'nativescorm')
	{
		if ($scormLesson)
		{
			$path = JURI::root() . 'administrator/index.php?option=com_tjlms&task=lesson.downloadMedia&mid=' . $lesson->media_id;
			$source = $lesson->source;
			$filepath = $lesson->org_filename;
			$filename = basename($filepath);

			$edit = 1;
			$lang_con_for_upload_formt_file = "PLG_TJSCORM_NATIVESCORM_UPLOADED_FORMAT_FILE";
			$file_browse_lang = "PLG_TJSCORM_NATIVESCORM_CHANGE";

			$passing_score = $scormLesson->passing_score;
			$grade_method = $scormLesson->grademethod;
		}
	}
} ?>

<div class="control-group">
	<div class="control-label"><label title="<?php echo Text::_($lang_con_for_upload_formt_file); ?>"><?php echo Text::_($lang_con_for_upload_formt_file);?></label></div>
	<div class="controls scorm_subformat" id="scorm_subformat_scorm">
		<div class="fileupload fileupload-new" data-provides="fileupload">
			<div class="input-append">
				<div class="uneditable-input span4">
					<span class="fileupload-preview">
						<?php echo $filename; ?>
					</span>
				</div>
				<span class="btn btn-primary btn-file">
					<span class="fileupload-new"><?php echo Text::_($file_browse_lang);?></span>
					<input type="file" id="scorm_upload" name="lesson_format[scorm]"
					accept="zip"
					data-upload-ajax="1">
				</span>

				<?php if($edit == 1){ ?>
					<a class="btn" target="_blank" href="<?php echo $path;?>">
						<span><?php echo Text::_("PLG_TJSCORM_NATIVESCORM_DOWNLOAD");?></span>
					</a>
					<a class="btn btn-primary" onclick="tjlmsAdmin.lesson.preview('previewContent', <?php echo $lesson_id; ?>);">
						<span><?php echo Text::_("PLG_TJSCORM_NATIVESCORM_PREVIEW");?></span>
					</a>

					<?php
						$link = Uri::root() . "index.php?option=com_tjlms&view=lesson&tmpl=component&lesson_id=" .  $lesson_id . "&mode=preview&ptype=" .  $lesson->format . "&isAdmin=1";

						echo HTMLHelper::_(
							'bootstrap.renderModal',
							'previewContent' . $lesson_id,
							array(
								'url'        => $link,
								'width'      => '800px',
								'height'     => '300px',
								'modalWidth' => '80',
								'bodyHeight' => '70'
							)
						);
					} ?>
			</div>

			<?php if($edit == 1){ ?>
			<div class="help">
				<?php echo Text::sprintf('COM_TJLMS_UPLOAD_FILE_WITH_EXTENSION','zip', $allowedFileSize);?>
			</div>
		<?php } ?>
		</div>
		<div style="clear:both"></div>
		<div class="format_upload_error alert alert-error" style="display:none" ></div>
		<div class="format_upload_success alert alert-info" style="display:none"></div>
		<input type="hidden" class="valid_extensions" value="zip"/>
	</div>
</div>

<!--div class="alert alert-info"><?php echo Text::_('PLG_TJSCORM_NATIVESCORM_MULTISCO_PARAMS_MSG') ?></div>

<div class="control-group">
	<div class="control-label">
		<label title="<?php echo Text::_('PLG_TJSCORM_NATIVESCORM_PASSING_SCORE')?>"><?php echo Text::_('PLG_TJSCORM_NATIVESCORM_PASSING_SCORE') ?></label>
	</div>
	<div class="controls">
		<input type="number" id="passing_score" name="lesson_format[nativescorm][passing_score]" value="<?php echo $passing_score; ?>" class="" aria-invalid="false">
	</div>
</div>

<div class="control-group">
	<div class="control-label">
		<label title="<?php echo Text::_('PLG_TJSCORM_NATIVESCORM_GRADE_METHOD')?>"><?php echo Text::_('PLG_TJSCORM_NATIVESCORM_GRADE_METHOD') ?></label>
	</div>
	<div class="controls">
		<?php
			$options[] = JHTML::_('select.option','0',Text::_('PLG_TJSCORM_NATIVESCORM_SELECT'));
			$options[] = JHTML::_('select.option','1',Text::_('PLG_TJSCORM_NATIVESCORM_NO_OF_LERAING_OBJECTS'));
			$options[] = JHTML::_('select.option','2',Text::_('PLG_TJSCORM_NATIVESCORM_HIGHEST_SCORE_AROSS_ALL'));
			$options[] = JHTML::_('select.option','3',Text::_('PLG_TJSCORM_NATIVESCORM_AVARAGE'));
			$options[] = JHTML::_('select.option','4',Text::_('PLG_TJSCORM_NATIVESCORM_SUM_OF_ALL'));
			echo  JHTML::_('select.genericlist', $options, 'lesson_format[nativescorm][grademethod]', 'class = "inputbox"', 'value','text', $grade_method);

			?>
	</div>
</div-->

<input type="hidden" id="subformatoption" name="lesson_format[nativescorm][subformatoption]" value="upload"/>
<input type="hidden" id="uploded_lesson_file" name="lesson_format[nativescorm][uploded_lesson_file]" value=""/>

<script type="text/javascript">

	/* Function to load the loading image. */
	function validatescormnativescorm(formid,format,subformat,media_id)
	{
		var res = {check: 1, message: ""};

		var format_lesson_form = jQuery("#lesson-format-form_"+ formid);

		var fileUploaded = jQuery("#lesson_format #" + format + " #uploded_lesson_file",format_lesson_form).val();

		var passingscore = jQuery("#lesson_format #" + format + " #passing_score",format_lesson_form).val();
		var grademethod = jQuery("#lesson_format #" + format + " #lesson_format_grademethod",format_lesson_form).val();

		if(media_id == 0)
		{
			if (!fileUploaded)
			{
				res.check = '0';
				res.message = "<?php echo Text::_('PLG_TJSCORM_NATIVESCORM_FILE_MISSING');?>";
			}
		}
		else
		{

			if (!fileUploaded)
			{
				res.check = 1;
			}
		}

		if(res.check == 1)
		{
			res.message = "<?php echo Text::_('PLG_TJSCORM_NATIVESCORM_VAL_PASSES');?>";
		}

		return res;
	}

</script>

