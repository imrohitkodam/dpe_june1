<?php
/**
 * @package Tjlms
 * @copyright Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     http://www.com
 */
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Utility\Utility;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

$cMax    = (int) $comp_params->get('lesson_upload_size', '0', 'INT');
if(method_exists('Utility', 'getMaxUploadSize'))
{
	$cMax = Utility::getMaxUploadSize($cMax . 'MB');
}
$allowedFileSize = HTMLHelper::_('number.bytes', $cMax);

$lang_con_for_upload_formt_file = "PLG_TJHTMLZIPS_HTMLZIP_UPLOAD_NEW_FORMAT";
$filename = "<div class='help-block'>" . Text::sprintf('COM_TJLMS_UPLOAD_FILE_WITH_EXTENSION','zip', $allowedFileSize) . "</div>";
$file_browse_lang = "PLG_TJHTMLZIPS_HTMLZIP_BROWSE";
$edit = 0;

$subformat = '';

$samplefileLink1 = Uri::root() . '/administrator/components/com_tjlms/html5-sample-1.zip';
$samplefileLink2 = Uri::root() . '/administrator/components/com_tjlms/html5-sample-2.zip';

if (!empty($lesson->sub_format))
{
	$subformat = $lesson->sub_format;

	$subformat_source_options = explode('.', $subformat);
	$source_plugin = $subformat_source_options[0];
	$source_option = $subformat_source_options[1];

	if (!empty($source_option) && $source_plugin == 'htmlzip')
	{
		$source = $lesson->source;
		$edit = 1;

		$path = Uri::root() . 'administrator/index.php?option=com_tjlms&task=lesson.downloadMedia&mid=' . $lesson->media_id;
		$filepath = $lesson->org_filename;
		$filename = basename($filepath);
		$lang_con_for_upload_formt_file = "PLG_TJHTMLZIPS_HTMLZIP_UPLOADED_FORMAT_FILE";
		$file_browse_lang = "PLG_TJHTMLZIPS_HTMLZIP_CHANGE";
	}
}
?>

<!-- Form elements to show if format is tjscorm -->

<div class="control-group">
	<div class="control-label"><label title="<?php echo Text::_($lang_con_for_upload_formt_file);?>"><?php echo Text::_($lang_con_for_upload_formt_file);?></label></div>
	<div class="controls htmlzips_subformat">
		<div class="fileupload fileupload-new" data-provides="fileupload">
			<div class="input-append">
				<div class="uneditable-input span4">
					<span class="fileupload-preview">
						<?php echo $filename; ?>
					</span>
				</div>
				<span class="btn btn-primary btn-file">
					<span class="fileupload-new"><?php echo Text::_($file_browse_lang);?></span>
					<input type="file" id="tjhtmlzips_upload" name="lesson_format[tjhtmlzips]"
					accept="zip"
					data-upload-ajax="1"/>
				</span>

				<?php if($edit == 1){ ?>
				<a class="btn" target="_blank" href="<?php echo $path;?>">
					<span><?php echo Text::_("PLG_TJHTMLZIPS_HTMLZIP_DOWNLOAD");?></span>
				</a>
				<a class="btn btn-primary" onclick="tjlmsAdmin.lesson.preview('previewContent', <?php echo $lesson_id; ?>);">
					<span><?php echo Text::_("PLG_TJHTMLZIPS_HTMLZIP_PREVIEW");?></span>
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
		</div>
		<?php if($edit == 1){ ?>
			<div class="help">
				<?php echo Text::sprintf('COM_TJLMS_UPLOAD_FILE_WITH_EXTENSION','zip', $allowedFileSize);?>
			</div>
		<?php } ?>
		<div style="clear:both"></div>
		<input type="hidden" class="valid_extensions" value="zip"/>
	</div>
</div>
<div class="control-group">
	<?php echo Text::sprintf('COM_TJLMS_DOWNLOAD_SAMPLE_FILE', $samplefileLink1, $samplefileLink2);?>
</div>
<input type="hidden" id="subformatoption" name="lesson_format[htmlzip][subformatoption]" value="upload"/>
<input type="hidden" id="uploded_lesson_file" name="lesson_format[nativescorm][uploded_lesson_file]" value=""/>
<script type="text/javascript">
	/* Function to load the loading image. */
	function validatehtmlzipshtmlzip(formid,format,subformat,media_id)
	{
		var res = {check: 1, message: ""};

		var format_lesson_form = jQuery("#lesson-format-form_"+ formid);

		var fileUploaded = jQuery("#lesson_format #" + format + " #uploded_lesson_file",format_lesson_form).val();

		if(media_id == 0)
		{
			if (!fileUploaded)
			{
				res.check = '0';
				res.message = "<?php echo Text::_('PLG_TJHTMLZIPS_HTMLZIP_FILE_MISSING');?>";
			}
		}

		if(res.check == 1)
		{
			res.message = "<?php echo Text::_('PLG_TJHTMLZIPS_HTMLZIP_VAL_PASSES');?>";
		}
		return res;
	}

</script>
