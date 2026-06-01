<?php
/**
 * @package Tjlms
 * @copyright Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     http://www.com
 */
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$html = '';
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
<?php
$sniffetFilePath = Uri::root(true) . '/plugins/tjtextmedia/' . $this->_name . '/' . $this->_name .'/assets/default/snippets.html';


$input = Factory::getApplication()->input;

$lesson_id = $input->get('lesson_id','0','INT');
$form_id	=	$input->get('form_id','0','STRING');

$media_id = 0;

if ($lesson_id)
{
	$media_id = $vars['media_id'];	//$this->lesson_data->media_id;
}

if($input->get('action','','string') == 'edit')
{
	if (isset ($vars['source']))
	{
		$html = $vars['source'];	//$this->lesson_typedata->source;
	}
}

?>

<link rel="stylesheet" type="text/css"  href="<?php echo Uri::root(true). '/plugins/tjtextmedia/' . $this->_name . '/' . $this->_name .'/assets/default/content.css'?>"></link>
<link rel="stylesheet" type="text/css"  href="<?php echo Uri::root(true). '/plugins/tjtextmedia/' . $this->_name . '/' . $this->_name .'/scripts/contentbuilder.css'?>"></link>
<link rel="stylesheet" type="text/css"  href="<?php echo Uri::root(true). '/media/com_tjlms/css/tjlms_backend.css'?>"></link>

<style>
	.htmltoolbar {width:100%;height:57px;border-top: #eee 1px solid;background:#d5d5d5;position:fixed;left: 0;top: 0;padding:10px;box-sizing:border-box;text-align:center;white-space:nowrap;z-index:1000;}
	.htmltoolbar button {border-radius:4px;padding: 10px 15px;text-transform:uppercase;font-size: 11px;letter-spacing: 1px;line-height: 1;}
	.container{margin-top: 79px !important;}
  </style>
<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?>">

<div class="md-modal" id="md-youtubsrc" style="max-width: 550px; display: none;"><div class="md-content"><div class="md-body"><input type="text" id="youtublink" class="inptxt"></div><div class="md-footer"><button type="button" id="btnSrcOk"> Ok </button></div></div></div>

<div class="md-modal md-show" id="md-customhtml" style="width: 45%; display:none;">
  <div class="md-content">
    <div class="md-body">
      <textarea id="txtCustomHtml" class="inptxt" style="height:350px;"></textarea>
    </div>
    <div class="md-footer">
      <button type="button" id="btnCustomHtmlOk"> Ok </button>
    </div>
  </div>
</div>


	<div id="tjlmscontainer">
		<div class="htmltoolbar" >
			<div class="left">
				<a href="javascript:save('save')" class="btn btn-small btn-primary"> <?php echo Text::_("COM_TJLMS_SAVE");?> </a>
				<a href="javascript:save('saveclose')" class="btn btn-small btn-success"> <?php echo Text::_("COM_TJLMS_SAVE_CLOSE");?> </a>
				<a href="javascript:closepopup()" class="btn btn-small btn-danger"> <?php echo Text::_("COM_TJLMS_CLOSE");?> </a>
			</div>
		</div>
		<div id="contentarea" class="container">
			<?php if ($html): ?>
				<?php     
					// Add contenteditable="false" to required divs
					$html = preg_replace(
						'/<div\s+style="margin:1em 0 2.5em;"/',
						'<div style="margin:1em 0 2.5em;" contenteditable="false"',
						$html
					);
					echo $html; 
				?>
			<?php else:
				ob_start();
				include($vars['template']);
				$html = ob_get_contents();
				ob_end_clean();
				echo $html;
			?>
			<?php endif; ?>

			<input type="hidden" id="currentmedia_id" name="currentmedia_id" value="<?php echo $media_id; ?> " />
			<input type="hidden" id="actionToPerform" value="" />
		</div>
	</div>
</div>
<script type="text/javascript" src="<?php echo Uri::root(true).'/media/jui/js/jquery.min.js'?>"></script>
<script type="text/javascript" src="<?php echo Uri::root(true) . '/plugins/tjtextmedia/' . $this->_name . '/' . $this->_name .'/scripts/jquery-ui.min.js'?>"></script>
<script type="text/javascript" src="<?php echo Uri::root(true) . '/plugins/tjtextmedia/' . $this->_name . '/' . $this->_name .'/scripts/contentbuilder.js'?>"></script>
<script type="text/javascript" src="<?php echo Uri::root(true) . '/plugins/tjtextmedia/' . $this->_name . '/' . $this->_name .'/scripts/saveimages.js'?>"></script>

<script type="text/javascript">

	jQuery(document).ready(function () {

		jQuery("#contentarea").contentbuilder({
			zoom: 0.85,
			snippetFile: '<?php echo $sniffetFilePath; ?>',
			toolbar: 'left',
            axis: 'y'
		});

		/* To get the sniffet toolbar auto open */
		jQuery( "#lnkToolOpen" ).trigger( "click" );

	});

	jQuery(window).scroll(function(e){

		/* To get the action toolbar fixed at top position*/
		$el = jQuery('.htmltoolbar');

		if (jQuery(this).scrollTop() > 50 && $el.css('position') != 'fixed'){
			jQuery('.htmltoolbar').css({'position': 'fixed', 'top': '0px'});
		}
		if (jQuery(this).scrollTop() < 50 && $el.css('position') == 'fixed')
		{
			jQuery('.htmltoolbar').css({'position': 'fixed', 'top': '0px'});
		}
	});

	/* Cancel Function to close modal popup */
	function closepopup()
	{
		var myModalEl = document.getElementById('openHtmlContent');
		var modal = bootstrap.Modal.getInstance(myModalEl)
		modal.hide();
		jQuery('#divCb').hide();
		window.location.reload();
	}

	/*  Save , Save and close functionality is done here */
	function save(action) {

		/* Save the action ..Used later in ajax */
		jQuery('#actionToPerform').val(action);

		/* Loading image to be shown during saving functioality is done completely */
		loadingImage();

		/* Save Images */
		jQuery("#contentarea").saveimages({
			handler: 'index.php?option=com_tjlms&task=callSysPlgin&plgType=tjtextmedia&plgName=<?php echo $vars['plgname'] ?>&plgtask=saveHtmlImages',
			onComplete: function () {
				/* Get Content */
				var sHTML = jQuery('#contentarea').data('contentbuilder').html();

				/* Save Content */
				jQuery.ajax({
					type: "POST",
					url: root_url+ "index.php?option=com_tjlms&task=callSysPlgin&plgType=tjtextmedia&plgName=<?php echo $vars['plgname'] ?>&plgtask=saveHtmlContent",
					data: {
						creator_id: <?php echo $vars['creator_id']; ?>,
						lesson_id: <?php echo $lesson_id; ?>,
						media_id: <?php echo $media_id; ?>,
						htmlcontent: sHTML
					},
					dataType: "JSON",
					async:false,
					success: function(data) {

						/* pass the media ID to parent window. Used in lesson saving */
						//window.parent.jQuery("input[name=media_id]").val(data);
						window.parent.jQuery("#lesson-format-form_<?php echo $form_id ; ?> #lesson_format_id").val(data);
						window.parent.jQuery("#lesson-format-form_<?php echo $form_id; ?> .tjlms_html_belonging_before_upload").hide().addClass("d-none");
						window.parent.jQuery("#lesson-format-form_<?php echo $form_id; ?> .tjlms_html_belonging_after_upload").show().removeClass("d-none");
						window.parent.jQuery("#lesson-format-form_<?php echo $form_id; ?> .tjlms_html_lesson_preview iframe").attr('data-src-preview', root_url+ "index.php?option=com_tjlms&view=lesson&tmpl=component&lesson_id=<?php echo $lesson_id; ?>&mode=preview&attempt=1&fs=1&close=0");

						var actionToPerform = jQuery('#actionToPerform').val();

						if (actionToPerform == 'saveclose')
						{
							closepopup();
							window.location.reload();
						}
						else
						{
							alert('Saved successfully');
							jQuery("#currentmedia_id").val(data);
						}

					},
					error: function(){
						console.log("something is wrong..");
					}

				});

				/* Close the hidding image once functionality is complete. */
				hideImage();

			}
		});

		jQuery("#contentarea").data('saveimages').save();

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

