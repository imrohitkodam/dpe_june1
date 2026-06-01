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

		if (!empty($source_option) && $source_plugin == 'pdfviewer')
		{
			$path = URI::root() . 'administrator/index.php?option=com_tjlms&task=lesson.downloadMedia&mid=' . $lesson->media_id;

			$filepath = $lesson->org_filename;
			$filename = basename($filepath);
?>
		<div class="control-group">
			<div class="control-label"><?php echo Text::_("COM_TJLMS_UPLOADED_FORMAT_FILE");?></div>
			<div  class="controls">

				<a target="_blank" href="<?php echo $path ?>"><?php echo $filename ?></a>

					<a class="btn btn-primary" onclick="tjlmsAdmin.lesson.preview('previewContent', <?php echo $lesson_id; ?>);" title="<?php echo Text::_('COM_TJLMS_PREVIEW_LESSON_DESC');?>"><?php echo Text::_('COM_TJLMS_PREVIEW_LESSON');?></a>
			</div>
		</div>

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
		}  ?>
<?php	}  ?>
<div class="control-group">
	<div class="control-label">
		<label title="<?php echo Text::_('COM_TJLMS_UPLOAD_FORMAT');?>">
			<?php echo Text::_("COM_TJLMS_UPLOAD_FORMAT") ?>
		</label>
	</div>
	<div  class="controls">
		<div class="document_upload">
			<div class="fileupload fileupload-new" data-provides="fileupload">
				<div class="input-append">
					<div class="uneditable-input span4">
						<span class="fileupload-preview">
							<?php echo Text::sprintf('COM_TJLMS_UPLOAD_FILE_WITH_EXTENSION', 'pdf', $allowedFileSize);?>
						</span>
					</div>
					<span class="btn btn-primary btn-file">
						<span class="fileupload-new">
							<?php echo Text::_("COM_TJLMS_BROWSE");?>
						</span>
						<input type="file"
							id="document_upload"
							name="lesson_format[<?php echo $plugin_name?>][document]"
							accept="pdf,PDF"
							data-upload-ajax="1"
						/>
					</span>
				</div>
			</div>
			<div style="clear:both"></div>
			<div class="format_upload_error alert alert-error" style="display:none" ></div>
			<div class="format_upload_success alert alert-info" style="display:none"></div>
		</div>
		<input type="hidden" class="valid_extensions" value="pdf"/>
		<input type="hidden" id="uploded_lesson_file" name="uploded_lesson_file" value=""/>
		<input type="hidden" id="subformatoption" name="lesson_format[pdfviewer][subformatoption]" value="upload"/>
	</div>
</div>


<script type="text/javascript">

/* Function to load the loading image. */
function validatedocumentpdfviewer(formid,format,subformat,media_id)
{
	var res = {check: 1, message: "<?php echo Text::_('PLG_TJDOC_BOXAPI_VAL_PASSES');?>"};
	var main_format_form = jQuery("#lesson-format-form_"+ formid);

	if(media_id == 0)
	{
		if (!jQuery("#lesson-format-form_"+ formid + " #lesson_format #" + format + " #uploded_lesson_file").val())
		{
			var res = {check: 0, message: "<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_FILE_MISSING');?>"};
		}
	}
	return res;
}

</script>

