<?php
/**
 * @package Tjlms
 * @copyright Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     http://www.com
 */
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

$preview_class = "d-none";
$source  = $html_src = '';
$user_id = Factory::getUser()->id;

$input = Factory::getApplication()->input;
$courseId = $input->get('cid', '', 'number');

$subformat = $source_plugin = $source_option = '';

if (!empty($lesson->sub_format))
{
	$subformat = $lesson->sub_format;
	$subformat_source_options = explode('.', $subformat);
	$source_plugin = $subformat_source_options[0];
	$source_option = $subformat_source_options[1];
}

if (!empty($source_option) && $source_plugin == 'nativeeditor')
{

	$source = $lesson->source;
	$preview_class = "";
		$html_src = JURI::root() . "index.php?option=com_tjlms&view=lesson&tmpl=component&lesson_id=" . $lesson_id . "&mode=preview&attempt=1&fs=1&close=0";

}
else
{
?>

	<div class="tjlms_html_belonging_before_upload" align="left">
		<a class="btn btn-primary article tjmodal" data-bs-target="#openNativeEditor" data-bs-toggle="modal" onclick="openNativebuilder(this,'add','openNativeEditor')"><?php echo Text::_("PLG_TEXTMEDIA_NATIVEBUILDR_LAUNCH_BUILDER");?></a>
	</div>
<?php } ?>


<div class="control-group tjlms_html_lesson_preview tjlms_html_belonging_after_upload <?php echo $preview_class;?>">
	<input type="hidden" id="textmedia_source" value="source"/>
	<iframe width="100%" height="400px" src="<?php echo $html_src ;?>"></iframe>
	<div class="tjlms_text_center">
		<a class="btn btn-success tjlms_html_belonging_after_upload <?php echo $preview_class;?>"
		data-bs-target="#openNativeEditor" data-bs-toggle="modal" onclick="openNativebuilder(this,'edit','openNativeEditor')"><?php echo Text::_("PLG_TEXTMEDIA_NATIVEBUILDR_HTML_CONTENT");?></a>
	</div>
</div>

<div class="modal fade" id="openNativeEditor" tabindex="-1" aria-labelledby="openNativeEditor" aria-hidden="true">
			<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body"></div>
			</div>
			</div>
		</div>
<script type="text/javascript">
/*open htmlcontentbuilder*/
function openNativebuilder(thislink,action,modalId = 'openNativeEditor')
{
	var format_form	=	jQuery(thislink).closest(".lesson-format-form");
	var format_form_id	=	jQuery(format_form).attr("id");
	var form_id	=	format_form_id.replace("lesson-format-form_","")

	var lesson_id	=	jQuery("[data-js-id='id']",format_form).val();

	var content_link = "<?php echo Uri::root();?>"+"index.php?option=com_tjlms&task=callSysPlgin&plgType=tjtextmedia&plgName=nativeeditor&plgtask=getpluginHtml&sub_layout=build&callType=1&tmpl=component&creator_id="+<?php echo $user_id?>+"&form_id="+ form_id +"&lesson_id=" + lesson_id +"&action="+ action+"&course_id="+<?php echo $courseId; ?>;

	/*var content_link = root_url+
						"index.php?option=com_tjlms&view=lesson&form_id="+ form_id +"&lesson_id=" + lesson_id +
						"&action="+ action +"&layout=default_html&sub_layout=creator&pluginToTrigger=' . $plugin_name . '&user_id=' . $user_id . '&tmpl=component" ;-*/

	var wwidth = jQuery(window).width()-10;
	var wheight = jQuery(window).height()-10;

	techjoomla.jQuery.ajax({
		url: content_link,
		type: "GET",
		cache: false,
		success: function(response)
		{
			jQuery('#' + modalId + " .modal-dialog");
			jQuery('#' + modalId + " .modal-content .modal-body").html(response);
		}
	});
}

function validatetextmedianativeeditor(formid,format,subformat,media_id)
{
	var res = {check: 1, message: ""};

	var format_lesson_form = jQuery("#lesson-format-form_"+ formid);

	if(media_id == 0)
	{
		if (!jQuery("#lesson_format #" + format + " #lesson_format_id",format_lesson_form).val())
		{
			res.check = '0';
			res.message = "<?php echo Text::_('PLG_TJTEXTMEDIA_NATIVEEDITOR_SOURCE_MISSING');?>";
		}
	}

	return res;
}
</script>
