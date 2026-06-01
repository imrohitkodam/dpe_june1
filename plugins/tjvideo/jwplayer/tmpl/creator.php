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

$subformat = '';

$cMax    = (int) $comp_params->get('lesson_upload_size', '0', 'INT');
if(method_exists('Utility', 'getMaxUploadSize'))
{
	$cMax = Utility::getMaxUploadSize($cMax . 'MB');
}
$allowedFileSize = HTMLHelper::_('number.bytes', $cMax);

if (!empty($lesson->sub_format))
{
		$subformat = $lesson->sub_format;
		$subformat_source_options = explode('.', $subformat);
		$source_plugin = $subformat_source_options[0];
		$source_option = $subformat_source_options[1];
		$source = $lesson->source;

		if (!empty($source_option) && $source_plugin == 'jwplayer')
		{
			$path = JURI::root() .'index.php?option=com_tjlms&task=lesson.downloadMedia&mid=' . $lesson->media_id;
			$filepath = $lesson->org_filename;
			$filename = basename($filepath);
	?>
			<div class="control-group">
				<div class="control-label"><label title="<?php echo Text::_("COM_TJLMS_SELECTED_VIDEO");?>"><?php echo Text::_("COM_TJLMS_SELECTED_VIDEO");?></label></div>
				<div  class="controls video_area">

						<a target="_blank" href="<?php echo $path ?>"><?php echo $filename ?></a>
						<a class="btn btn-primary" onclick="tjlmsAdmin.lesson.preview('<?php echo $lesson_id?>')" title="<?php echo Text::_('COM_TJLMS_PREVIEW_LESSON_DESC');?>"><?php echo Text::_('COM_TJLMS_PREVIEW_LESSON');?></a>
				</div>
			</div>

	<?php } ?>
<?php } ?>
<div class="control-group">
	<div class="control-label"><label title="<?php echo Text::_("COM_TJLMS_VIDEO_FORMAT_OPTIONS");?>"><?php echo Text::_("COM_TJLMS_VIDEO_FORMAT_OPTIONS");?></label></div>

	<div  class="controls">
		<div id="video_package">
			<div class="fileupload fileupload-new" data-provides="fileupload">
				<div class="input-append">
					<div class="uneditable-input span4">
						<span class="fileupload-preview">
						<?php echo
						Text::sprintf('COM_TJLMS_UPLOAD_FILE_WITH_EXTENSION', 'flv, mp4, mp3', $allowedFileSize);?>
						</span>
					</div>
					<span class="btn btn-primary btn-file">
						<span class="fileupload-new"><?php echo  Text::_("COM_TJLMS_BROWSE");?></span>
						<input type="file" id="video_upload"
								name="lesson_format[upload]"
								accept="flv,mp4,mp3"
								data-upload-ajax="1">
					</span>
				</div>
			</div>
			<div style="clear:both"></div>
			<div class="format_upload_error alert alert-error" style="display:none" ></div>
			<div class="format_upload_success alert alert-info" style="display:none"></div>
		</div>
		<input type="hidden" class="valid_extensions" value="flv,mp4,mp3"/>
		<input type="hidden" id="uploded_lesson_file" name="lesson_format[jwplayer][upload]" value=""/>
		<input type="hidden" id="subformatoption" name="lesson_format[jwplayer][subformatoption]" value="upload"/>

	</div>
</div>

<script type="text/javascript">

	/* Function to load the loading image. */
	function validatevideojwplayer(formid,format,subformat,media_id)
	{
		var res = {check: 1, message: "PLG_TJVIDEO_JWPLAYER_VAL_PASSES"};

		var val_passed = '0';
		if(media_id == 0)
		{
			var format_lesson_form = jQuery("#lesson-format-form_"+ formid);

			if (!jQuery("#lesson_format #" + format + " #uploded_lesson_file",format_lesson_form).val())
			{
				res.check = '0';
				res.message = "<?php echo Text::_('PLG_TJVIDEO_JWPLAYER_FILE_MISSING');?>";
			}
		}
		return res;
	}

</script>

