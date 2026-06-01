<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,TJTEXTMEDIA,lessonlink
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die('Restricted access');

$source        = (isset($lesson->source)) ? $lesson->source : '';
$preview_class = "tjlms_display_none";
$source_plugin = $source_option = '';
$subformat     = '';

if (!empty($lesson->sub_format))
{
	$subformat = $lesson->sub_format;
	$subformat_source_options = explode('.', $subformat);
	$source_plugin = $subformat_source_options[0];
	$source_option = $subformat_source_options[1];
}
?>
<link rel="stylesheet" type="text/css"
	href="<?php echo Uri::root(true) . '/plugins/tjtextmedia/' . $this->_name . '/' . $this->_name . '/style/tjlmslessonlink.css';?>"></link>
<div class="control-group"></div>
<?php
	$params = json_decode($lesson->media['params']);

	$checkedNo = $checkedYes = '';

	if ($params->readerview == 1)
	{
		$checkedYes = 'checked="checked"';
	}

	if ($params->readerview == 0)
	{
		$checkedNo = 'checked="checked"';
	}

?>
<div class="control-group">
	<div class="control-label">
		<label id="jform_readerview-lbl"
		for="jform_readerview" class="hasTooltip" title="<?php echo Text::_("PLG_TJTEXTMEDIA_LAL_RENDER_LINK_DESC");?>">
		<?php echo Text::_("PLG_TJTEXTMEDIA_LAL_RENDER_LINK_TITLE");?>
		</label>
	</div>
	<div class="controls">
		<fieldset id="jform_readerview" class="btn-group radio">
			<input type="radio" id="jform_readerview0" name="jform[readerview]" value="1" <?php echo $checkedYes;?>>
			<label for="jform_readerview0" class="btn">
				<?php echo Text::_("JYES");?>
			</label>

			<input type="radio" id="jform_readerview1" name="jform[readerview]" value="0" <?php echo $checkedNo;?> >
			<label for="jform_readerview1" class="btn">
			<?php echo Text::_("JNO");?>
			</label>
		</fieldset>
		<input type="hidden" id="tjlmslessonlink_params" name="lesson_format[tjlmslessonlink][params]" />
	</div>
</div>

<?php
if (!empty($source_option) && $source_plugin == 'tjlmslessonlink')
{
	$source  = trim($lesson->source);
	$URL     = $lesson->source;
	$URL_len = (strlen($URL) > 95 ? 'url_len' : '');
	?>
	<div class="control-group">
		<div class="control-label">
			<label title="<?php echo Text::_("PLG_TJTEXTMEDIA_LAL_SELECTED_URL");?>">
				<?php echo Text::_("PLG_TJTEXTMEDIA_LAL_SELECTED_URL_TITLE");?></label>
		</div>
		<div  class="controls">
			<span class="input-medium selected_url <?php echo $URL_len;?>"
				title="<?php echo $URL;?>">
			<?php echo $URL;?>
			</span>
			<span class="input-append">
				<a class="btn btn-primary" onclick="tjlmsAdmin.lesson.preview('previewContent', <?php echo $lesson_id; ?>);"
					title="<?php echo Text::_('PLG_TJTEXTMEDIA_LAL_PREVIEW_TITLE');?>" role="button">
					<?php echo Text::_('PLG_TJTEXTMEDIA_LAL_PREVIEW');?>
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
							'bodyHeight' => '80'
						)
					);?>
			</span>
		</div>
	</div>
<?php
}
?>
<div class="control-group">
	<div class="controls">
		<div class="alert alert-info">
			<span><?php echo Text::_('PLG_TJTEXTMEDIA_LAL_INVALID_URL_NOTE');?></span>
		</div>
	</div>
</div>
<div class="control-group">
	<div class="control-label">
		<label id="jform_link-lbl" for="jform_link" class="hasTooltip"
			title="<?php echo Text::_('PLG_TJTEXTMEDIA_LAL_URL_TITLE');?>">
			<?php echo Text::_('PLG_TJTEXTMEDIA_LAL_URL');?>
		</label>
	</div>
	<div class="controls">
		<textarea id="tjlmslessonlink_url" cols="50" class="input-block-level"
			rows="2" name="lesson_format[tjlmslessonlink][url]"></textarea>
		<input type="hidden" id="subformatoption" name="lesson_format[tjlmslessonlink][subformatoption]" value="url"/>
	</div>
</div>

<script>
	jQuery(document).ready(function()
	{
		var readableVal = jQuery('input[name="jform[readerview]"]:checked').val();
		var source     = {readerview: readableVal};
		var jsonString = JSON.stringify(source);

		jQuery("#tjlmslessonlink_params").val(jsonString);

		jQuery(document).on("change", 'input[name="jform[readerview]"]', function () {
				readableVal = jQuery(this).val();
				source      = {readerview: readableVal};
				jsonString  = JSON.stringify(source);

				jQuery("#tjlmslessonlink_params").val(jsonString);
		});
	});

function isValidUrl(){
	var res = {check: 1, message: ""};
	var url=document.getElementById("tjlmslessonlink_url").value;
	var regexp = /(ftp|http|https):\/\/\w+(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;

	if (url.match(/\s/g)){
		document.getElementById("tjlmslessonlink_url").focus();
		return false;
	}
	else if (regexp.test(url) == 1)
	{
		return true;
	}
	else
	{
		document.getElementById("tjlmslessonlink_url").focus();
		return false;
	}
}
function validatetextmediatjlmslessonlink(formid,format,subformat,media_id){
	var res = {check: 1, message: ""};
	var format_lesson_form = jQuery("#lesson-format-form_"+ formid);
	var selectedURL, newURL;
	selectedURL = jQuery("#lesson_format #" + format + " [data-subformat='tjlmslessonlink'] .selected_url",format_lesson_form).text();
	newURL = jQuery("#lesson_format #" + format + " [data-subformat='tjlmslessonlink'] #tjlmslessonlink_url",format_lesson_form).val();

	if(selectedURL.trim() =='' && newURL.trim() =='')
	{
		res.check = '0';
		res.message = "<?php echo Text::_('PLG_TJTEXTMEDIA_LAL_URL_MISSING');?>";
		return res;
	}

	if (((newURL != '' && isValidUrl(newURL) == true)) || (selectedURL != '' && newURL == '') || (selectedURL != '' && newURL != '' && isValidUrl(newURL) == true))
	{
		return res;
	}

	res.check = '0';
	res.message = "<?php echo Text::_('PLG_TJTEXTMEDIA_LAL_ENTER_VALID_URL');?>";
	return res;
}
</script>
