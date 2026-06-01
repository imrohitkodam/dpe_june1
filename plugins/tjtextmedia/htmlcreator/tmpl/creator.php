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
$source = '';
$html_src = 'about:blank';
$user_id = Factory::getUser()->id;

$subformat = $source_plugin = $source_option = '';

if (!empty($lesson->sub_format))
{
	$subformat = $lesson->sub_format;
	$subformat_source_options = explode('.', $subformat);
	$source_plugin = $subformat_source_options[0];
	$source_option = $subformat_source_options[1];
}

if (!empty($source_option) && $source_plugin == 'htmlcreator')
{
	$subformat_source_options = explode('.', $subformat);
	$source_option = $subformat_source_options[1];


		$source = $lesson->source;
		$preview_class = "";
		$html_src	=	JURI::root() . "index.php?option=com_tjlms&view=lesson&tmpl=component&lesson_id=" . $lesson_id . "&mode=preview&attempt=1&fs=1&close=0";
}
else
{
	?>
<div class="tjlms_html_belonging_before_upload" align="left">
	<a class="btn btn-primary article tjmodal" data-bs-target="#openHtmlContent"
								data-bs-toggle="modal"
	onclick="openHtmlContentbuilder(this,'add','openHtmlContent')"><?php echo Text::_("COM_TJLMS_LAUNCH_HTML_BUILDER");?></a>
</div>
<?php } ?>

<style>

.hide-important {
    display: none !important;
}

.embed-responsive {
    position: relative;
}

</style>
<div class="control-group tjlms_html_lesson_preview tjlms_html_belonging_after_upload <?php echo $preview_class;?>">
	<input type="hidden" id="textmedia_source" value="source"/>
	<iframe width="100%" height="400px" src="<?php echo $html_src ;?>"></iframe>
	<div class="tjlms_text_center">
		<a class="btn btn-success tjlms_html_belonging_after_upload <?php echo $preview_class;?>"
			data-bs-target="#openHtmlContent"
								data-bs-toggle="modal"
			onclick="openHtmlContentbuilder(this,'edit','openHtmlContent')"><?php echo Text::_("COM_TJLMS_EDIT_HTML_CONTENT")?></a>

		<div class="modal fade" id="openHtmlContent" tabindex="-1" aria-labelledby="openHtmlContent" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
			<div class="modal-dialog">
			<div class="modal-content">
				<!-- <div class="modal-header">
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div> -->
				<div class="modal-body"></div>
			</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
/*open htmlcontentbuilder*/

function openHtmlContentbuilder(thislink,action, modalId = 'openHtmlContent')
{
	var format_form	=	jQuery(thislink).closest(".lesson-format-form");
	var format_form_id	=	jQuery(format_form).attr("id");
	var form_id	=	format_form_id.replace("lesson-format-form_","")

	var lesson_id	=	jQuery("[data-js-id='id']",format_form).val();

	/*var content_link = root_url +
						"index.php?option=com_tjlms&view=lesson&format=raw&form_id="+ form_id +"&lesson_id=" + lesson_id +"&action="+ action +
						"&layout=default_html&sub_layout=creator&pluginToTrigger=' . $plugin_name . '&user_id=' . $user_id . '&tmpl=component";*/

	var getModalUrl = "<?php echo Uri::root();?>"+"index.php?option=com_tjlms&task=callSysPlgin&plgType=tjtextmedia&plgName=htmlcreator&plgtask=getpluginHtml&sub_layout=build&callType=1&tmpl=component&creator_id="+<?php echo $user_id?>+"&form_id="+ form_id +"&lesson_id=" + lesson_id +"&action="+ action;

	techjoomla.jQuery.ajax({
		url: getModalUrl,
		type: "GET",
		cache: false,
		success: function(response)
		{
			jQuery('#' + modalId + " .modal-dialog").addClass('modal-dialog-scrollable modal-lg lesson-modal');
			jQuery('#' + modalId + " .modal-content .modal-body").html(response);
			jQuery(".tjlms_html_belonging_after_upload").show().removeClass("d-none");
			jQuery('#divCb').show();
		}
	});
}

/* Function to load the loading image. */
function validatetextmediahtmlcreator(formid,format,subformat,media_id)
{
	var res = {check: 1, message: ""};

	var format_lesson_form = jQuery("#lesson-format-form_"+ formid);

	if(media_id == 0)
	{
		if (!jQuery("#lesson_format #" + format + " #lesson_format_id",format_lesson_form).val())
		{
			res.check = '0';
			res.message = "<?php echo Text::_('PLG_TJTEXTMEDIA_HTMLCREATOR_SOURCE_MISSING');?>";
		}
	}

	return res;
}
</script>
