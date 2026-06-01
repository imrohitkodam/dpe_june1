<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
JHtml::_('behavior.core');

use Joomla\CMS\Factory;
use Joomla\CMS\Editor\Editor;

$reqURI = Uri::root();

// If host have wwww, but Config doesn't.
if (isset($_SERVER['HTTP_HOST']))
{
	if ((substr_count($_SERVER['HTTP_HOST'], "www.") != 0) && (substr_count($reqURI, "www.") == 0))
	{
		$reqURI = str_replace("://", "://www.", $reqURI);
	}
	elseif ((substr_count($_SERVER['HTTP_HOST'], "www.") == 0) && (substr_count($reqURI, "www.") != 0))
	{
		// Host do not have 'www' but Config does
		$reqURI = str_replace("www.", "", $reqURI);
	}
}
?>
<base href="<?php echo Uri::root()?>" >
<script>
var root_url = "<?php echo $reqURI;?>"
</script>
<script type="text/javascript" src="<?php echo Uri::root(true) . '/media/system/js/core.js'?>"></script>
<script type="text/javascript"  src="<?php echo Uri::root(true) . '/media/vendor/tinymce/tinymce.min.js'?>"></script>
<!-- <script type="text/javascript"  src="<?php echo Uri::root(true) . '/media/editors/tinymce/js/tinymce.min.js'?>"></script> -->
<style>
	.htmltoolbar {width:100%;height:57px;border-top: #eee 1px solid;background:#d5d5d5;position:fixed;left: 0;top: 0;padding:10px;box-sizing:border-box;text-align:center;white-space:nowrap;z-index:1000;}
	.htmltoolbar button {border-radius:4px;padding: 10px 15px;text-transform:uppercase;font-size: 11px;letter-spacing: 1px;line-height: 1;}
	.container{margin-top: 79px !important;}
  </style>
<?php
$html = '';
$input       = Factory::getApplication()->input;
$editor_name = Factory::getUser($vars['creator_id'])->getParam("editor");

if (empty($editor_name))
{
	$conf        = Factory::getConfig();
	$editor_name = $conf->get('editor');
}

$lesson_id   = $input->get('lesson_id', '0', 'INT');
$form_id     = $input->get('form_id', '0', 'STRING');

$media_id = 0;

if ($lesson_id)
{
	if (isset($vars['media_id']))
	{
		$media_id = $vars['media_id'];
	}
}

if (isset($vars['source']))
{
	$html = $vars['source'];

}
?>

<div class="<?php echo COM_TJLMS_WRAPPER_DIV;?>">
<form id="nativeeditor" method="post" >
	<div id="tjlmscontainer">
		<div class="htmltoolbar" >
			<div class="left">
				<button type="button" onclick="save('save')" class="btn btn-small btn-primary"> <?php
					echo Text::_("COM_TJLMS_SAVE");
					?>
				</button>
				<button type="button" onclick="save('saveclose')" class="btn btn-small btn-success">
					<?php echo Text::_("COM_TJLMS_SAVE_CLOSE");?>
				</button>
				<button type="button" onclick="closepopup()" class="btn btn-small btn-danger">
					<?php echo Text::_("COM_TJLMS_CLOSE");?>
				</button>
			</div>
		</div>
		<div id="contentarea" class="container">
			<?php
			if (!$html)
			{
				ob_start();
				include $vars['template'];
				$html = ob_get_contents();
				ob_end_clean();
			}
			?>
			   <?php

				$editor = Editor::getInstance($editor_name);

				$params = array('smilies'=>'0', 'style'=>'1', 'layer'=>'0', 'table'=>'0', 'clear_entities'=>'0');

				echo $editor->display("contentbuilder", htmlspecialchars($html, ENT_COMPAT, 'UTF-8'), '100%', '600', '90', '50', true, null, null, null, $params);

?>
		   <input type="hidden" id="currentmedia_id" name="currentmedia_id" value="<?php
echo $media_id;
?> " />
			<input type="hidden" id="user_id" value="<?php
echo $vars['creator_id'];
?>" />
			<input type="hidden" id="lesson_id" value="<?php
echo $lesson_id;
?>" />
			<input type="hidden" id="media_id" value="<?php
echo $media_id;
?>" />

			<input type="hidden" id="actionToPerform" value="" />
		</div>
	</div>
</form>
</div>
<script type="text/javascript" src="<?php echo Uri::root(true).'/media/vendor/jquery/js/jquery.min.js'?>"></script>

<script type="text/javascript">

	/* Cancel Function to close modal popup */
	function closepopup()
	{
		var myModalEl = document.getElementById('openNativeEditor');
		var modal = bootstrap.Modal.getInstance(myModalEl)
		modal.hide();
	}

	/*  Save , Save and close functionality is done here */
	function save(action) {
		/* Save the action ..Used later in ajax */
		jQuery('#actionToPerform').val(action);

		/* Loading image to be shown during saving functioality is done completely */
		loadingImage();
		/* Get Content */
		var editor="<?php echo $editor_name; ?>";

		if(editor=='tinymce')
		{
			var sHTML = jQuery("iframe").contents().find("body#tinymce").html();
		}
		else if (editor == 'none' )
		{
			var sHTML = jQuery('textarea[name="contentbuilder"]').val();
		}
		else
		{
			var sHTML = jQuery("iframe").contents().find("body").html(); //cke_show_borders
		}

		if (!sHTML)
		{
			var sHTML = jQuery('textarea[name="contentbuilder"]').val();
		}

		/* Get media id */
		var media_id = jQuery('#currentmedia_id').val();

		/* Save Content */
		jQuery.ajax({
			url: "<?php echo Uri::root();?>"+"index.php?option=com_tjlms&task=callSysPlgin&plgType=tjtextmedia&plgName=<?php echo $vars['plgname'];?>&plgtask=saveHtmlContent",
			type: "POST",
			data: {
				user_id: <?php echo $vars['creator_id'];?>,
				lesson_id: <?php echo $lesson_id;?>,
				media_id: media_id,
				htmlcontent: sHTML
			},
			dataType: "JSON",
			async:false,
			success: function(data) {

				/* pass the media ID to parent window. Used in lesson saving */
				//window.parent.jQuery("input[name=media_id]").val(data);
				window.parent.jQuery("#lesson-format-form_<?php echo $form_id;?> #lesson_format_id").val(data);
				window.parent.jQuery("#lesson-format-form_<?php echo $form_id;?> .tjlms_html_belonging_before_upload").hide().addClass("d-none");
				window.parent.jQuery("#lesson-format-form_<?php echo $form_id;?> .tjlms_html_belonging_after_upload").show().removeClass("d-none");
				window.parent.jQuery("#lesson-format-form_<?php echo $form_id;?> .tjlms_html_lesson_preview iframe").attr('src', root_url+ "index.php?option=com_tjlms&view=lesson&tmpl=component&lesson_id=<?php echo $lesson_id;?>&mode=preview&attempt=1&fs=1");

				var actionToPerform = jQuery('#actionToPerform').val();

				if (actionToPerform == 'saveclose')
				{
					closepopup();
				}
				else
				{
					alert('Saved successfully');
					jQuery("#currentmedia_id").val(data);
				}
				hideImage();

			},
			error: function(jqXHR, textStatus, errorThrown)
			{
				status.setMsg(jqXHR.responseText,'alert-error');
			}
		});
	}

	/* Function to load the loading image. */
	function loadingImage()
	{
		jQuery('<div id="appsloading"></div>')
		.css("background", "rgba(255, 255, 255, .8) url('"+root_url+"components/com_tjlms/assets/images/ajax.gif') 50% 15% no-repeat")
		.css("top", jQuery('#tjlmscontainer').position().top - jQuery(window).scrollTop())
		//.css("left", jQuery('#contentarea').position().left - jQuery(window).scrollLeft())
		.css("width", jQuery('#tjlmscontainer').width())
		.css("height", jQuery('#tjlmscontainer').height())
		.css("position", "fixed")
		.css("z-index", "1000")
		.css("opacity", "0.80")
		.css("-ms-filter", "progid:DXImageTransform.Microsoft.Alpha(Opacity = 80)")
		.css("filter", "alpha(opacity = 80)")
		.appendTo('#tjlmscontainer');
	}

	/* Function to close the loading image. */
	function hideImage()
	{
		jQuery('#appsloading').remove();
	}

</script>
