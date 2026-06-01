<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,TJVideo,kpoint
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');
jimport('joomla.client.http');

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\Http\Http;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\PluginHelper;

$cssfile = Uri::root(true) . '/plugins/tjvideo/kpoint/assets/css/kpoint.css';
?>
<link rel="stylesheet" type="text/css" href="<?php echo $cssfile; ?>">
<?php

$subformat = '';

if (!empty($lesson->sub_format))
{
	$subformat = $lesson->sub_format;

	$subformat_source_options = explode('.', $subformat);
	$source_option = $subformat_source_options[1];
	$source = $lesson->source;

	if (!empty($source_option))
	{
		$path     = Uri::root() . 'media/com_tjlms/lessons/' . $lesson->source;
		$filepath = $lesson->org_filename;
		$filename = basename($filepath);
	}

	// Trigger all sub format  video plugins method that renders the video player
	PluginHelper::importPlugin('tjvideo', 'kpoint');
	$statusRs = Factory::getApplication()->triggerEvent('getKapsuleStatus', $source);

}
?>
<div class="container-fluid"><?php
	if (!empty($source_option))
	{ ?>
		<div class="row-fluid"><?php
			if (isset($statusRs[0]['status']) && $statusRs[0]['status'] == 'processing' && ($statusRs[0]['published_flag']=='' || $statusRs[0]['published_flag'] == false))
			{
				echo '<div class=" span12 alert alert-warning">' . Text::_('PLG_TJVIDEO_KPOINT_UPLOADED_VIDEO_INPROCESS') . '</div>';
			}
			elseif (isset($statusRs[0]['status']) == 'ready' && ($statusRs[0]['published_flag']=='' || $statusRs[0]['published_flag'] == false))
			{
				echo '<div class="span12 alert alert-warning" id="publish_message">' . Text::_('COM_TJLMS_CREATE_VIDEO_KPINT_PUBLISH_MSG') . '</div>';
			}
			else
			{
				echo '<div class="span12"></div>';
			} ?>
		</div><?php
	} ?>
	<div class="row-fluid kPoint-border">
		<div class="span2">
			<img class="kpoint_href" src="<?php echo $statusRs[0]['thumbnail_url'];?>" alt="">
		</div>
		<div class="span9 kPoint-det"><?php
			if (!empty($source_option))
			{?>

				<div class="kPoint-title kpoint_text"><?php echo (isset($statusRs[0]['displayname'])) ? $statusRs[0]['displayname'] : ""; ?></div>
				<div class="kpoint-owner row">
					<div class="kpoint_ownername">
						<i class="icon-user pull-left"></i>
						<?php echo (isset($statusRs[0]['owner_displayname'])) ? $statusRs[0]['owner_displayname'] : ""; ?>
					</div>
				</div>
				<?php
			}
			else
			{ ?>
				<div class="kPoint-title kpoint_text"><?php echo Text::_("PLG_TJVIDEO_KPOINT_DEFULT_VIDEO_NAME");?></div>
				<div class="kpoint_ownername">&nbsp;</div>
				<?php
			} ?>
			<div class="kPoint-selectVideo" id="video_textarea"><?php
				$link = Uri::root() . 'index.php?option=com_tjlms&task=callSysPlgin&plgType=tjvideo&plgName=kpoint&plgtask=getHtml&callType=1&lesson_id=' . $lesson_id; ?>
				<a class="btn btn-primary" onClick="openListVideos(this,'<?php echo $link;?>');" rel="{size: {x: 700, y: 300}}"><i class="icon-list icon-white"></i>
					<?php echo Text::_("COM_TJLMS_VIDEO_KPINT_BTN");?>
				</a>
				<input type="hidden" id="video_url" value="<?php echo $lesson->source;?>" class="kpoint_video input-block-level" cols="50" rows="2" name="lesson_format[kpoint][url]" >
			</div>
			<div class="span4 kPoint-selectVideo" id="video_creater" ><?php
				// Trigger all sub format  video plugins method that renders the video player
				PluginHelper::importPlugin('tjvideo', 'kpoint');
				$result = Factory::getApplication()->triggerEvent('getCreateVideoHtml');
				$linkx = $result[0];
				if ($this->params->get('create_video')) { ?>
				<a class="btn btn-primary" href="<?php echo $linkx;?>" onclick="openVideoCreater(this)" target="_blank">
					<i class="icon-pencil icon-white"></i><?php echo Text::_("COM_TJLMS_CREATE_VIDEO_KPINT_BTN");?>
				</a><?php
				} else {
					if ($this->params->get('download_video')) {
							$downloadLink = $this->params->get('domain_name') . '/files/download/video.mp4'; ?>
							<a download="video" class="btn btn-primary download-btn" href="<?php echo $downloadLink;?>">
								<i class="icon-download icon-white"></i><?php echo Text::_("COM_TJLMS_CREATE_VIDEO_KPINT_DOWNLOAD_VIDEO") ?>
							</a><?php
					}
				}?>
			</div>
			<div class="span4 kPoint-selectVideo"><?php
				if (!empty($source_option))
				{
					if (isset($statusRs[0]['status']) && $statusRs[0]['status'] == 'ready' && (isset($statusRs[0]['published_flag']) && $statusRs[0]['published_flag'] =='' || $statusRs[0]['published_flag'] == false))
					{
						$link = Uri::root() . 'index.php?option=com_tjlms&task=callSysPlgin&plgType=tjvideo&plgName=kpoint&plgtask=getKapsuleStatusUpdate&callType=1&source=' . $source; ?>
						<a class="btn btn-success" id="publish_button" onclick="PublishVideo(this.id, '<?php echo $link;?>')">
							<i class="icon-plus icon-white"></i>
							<?php echo Text::_("COM_TJLMS_CREATE_VIDEO_KPINT_PUBLISH") ?>
						</a>
						<?php
						if ($this->params->get('create_video')) {
							if ($this->params->get('download_video')) {
								$downloadLink = $this->params->get('domain_name') . '/files/download/video.mp4'; ?>
								<a download="video" class="btn btn-primary download-btn hide" href="<?php echo $downloadLink;?>">
									<i class="icon-download icon-white"></i><?php echo Text::_("COM_TJLMS_CREATE_VIDEO_KPINT_DOWNLOAD_VIDEO") ?>
								</a><?php
							}
						}
					}
					elseif (isset($statusRs[0]['status']) == 'ready' && ($statusRs[0]['published_flag']!='' || $statusRs[0]['published_flag'] != false))
					{
						if ($this->params->get('create_video')) {
							if ($this->params->get('download_video')) {
								$link = Uri::root() . 'index.php?option=com_tjlms&task=callSysPlgin&plgType=tjvideo&plgName=kpoint&plgtask=getKapsuleDownload&callType=1&source=' . $source;
								$downloadLink = $this->params->get('domain_name') . '/files/download/video.mp4';
								// $statusDownload = $dispatcher->trigger('getKapsuleDownload', $source); ?>
								<!--a download="video" class="btn btn-primary" onclick="hitURL('<?php echo $link;?>')"><?php echo Text::_("COM_TJLMS_CREATE_VIDEO_KPINT_DOWNLOAD_VIDEO") ?> </a-->
								<a download="video" class="btn btn-primary" href="<?php echo $downloadLink;?>">
									<i class="icon-download icon-white"></i><?php echo Text::_("COM_TJLMS_CREATE_VIDEO_KPINT_DOWNLOAD_VIDEO") ?>
								</a><?php
							}
						}
					}
				}?>
			</div>
		</div>
	</div>
</div>
<input type="hidden" id="subformatoption" name="lesson_format[kpoint][subformatoption]" value="url"/>
<script type="text/javascript">

	var thisbtnData = '';

	function openVideoCreater(thisbtn)
	{
		var format_formx	=	jQuery(thisbtn).closest('.lesson-format-form');
		var format_form_idx	=	jQuery(format_formx).attr('id');
		window.thisbtnData	=	format_form_idx.replace('lesson-format-form_','');
	}

	function receiveMessage(e) {
		// Update the div element to display the message.
		var gccId = JSON.parse (e.data);

		jQuery("#lesson-format-form_" + window.thisbtnData +" .kpoint_video").val(gccId['id']);

		jQuery.ajax({
			url:  '<?php echo Uri::root();?>index.php?option=com_tjlms&task=callSysPlgin&plgType=tjvideo&plgName=kpoint&plgtask=getKapsuleData&callType=1&source=' + gccId['id'],
			success: function(response)
			{
				var arr = JSON.parse(response);
				jQuery("#lesson-format-form_" + window.thisbtnData +" .kpoint_text").text(arr['displayname']);
				jQuery("#lesson-format-form_" + window.thisbtnData +" .kpoint_href").attr("src", arr['thumbnail_url']);
			}
		});

		jQuery.ajax({
			url:  '<?php echo Uri::root();?>index.php?option=com_tjlms&task=callSysPlgin&plgType=tjvideo&plgName=kpoint&plgtask=updateLessonState&callType=1&lesson_state=0&lesson_id=<?php echo $lesson->lesson_id;?>',
			success: function(response)
			{
				jQuery("#lesson-basic-form_" + window.thisbtnData  +" label[for=jform_state0"+window.thisbtnData+"]").removeClass('active btn-success');
				jQuery("#lesson-basic-form_" + window.thisbtnData  +" label[for=jform_state1"+window.thisbtnData+"]").addClass('active btn-danger');
				jQuery("#lesson-basic-form_" + window.thisbtnData  +" input[id=jform_state0"+window.thisbtnData+"]").removeAttr('checked');
				jQuery("#lesson-basic-form_" + window.thisbtnData  +" input[id=jform_state1"+window.thisbtnData+"]").attr('checked', 'checked');
			}
		});
	}

	// Setup an event listener that calls receiveMessage() when the window
	// receives a new MessageEvent.
	window.addEventListener("message", receiveMessage);

	function bindKpoint(form_id,ids,displayname,href,des, ownername)
	{
		if (VarsameLessonName == 1)
		{
			jQuery("#lesson-basic-form_" + form_id  +" input[id=jform_name]").val(displayname);
		}

		jQuery("#lesson-basic-form_" + form_id  +" textarea[id=jform_description]").val(des);

		jQuery("#lesson-format-form_" + form_id +" .kpoint_video").val(ids);
		jQuery("#lesson-format-form_" + form_id +" .kpoint_text").text(displayname);
		jQuery("#lesson-format-form_" + form_id +" .kpoint_ownername").html('<i class="icon-user pull-left"></i>').append(ownername);
		jQuery("#lesson-format-form_" + form_id +" .kpoint_href").attr("src", href);
		jQuery("#lesson-format-form_" + form_id +" #publish_button").addClass('hide');
		jQuery("#lesson-format-form_" + form_id +" #publish_message").addClass('hide');
		jQuery("#lesson-format-form_" + form_id +" .download-btn").removeClass('hide');

		window.parent.SqueezeBox.close();
	}

	function openListVideos(thisbtn,url)
	{
		var format_form    = jQuery(thisbtn).closest('.lesson-format-form');
		var format_form_id = jQuery(format_form).attr('id');
		var form_id        = format_form_id.replace('lesson-format-form_','');

		var wwidth  = jQuery(window).width()-400;
		var wheight = jQuery(window).height()-100;

		url += "&form_id=" + form_id;
		console.log(url);
		SqueezeBox.open(url, {
			size: {x: wwidth, y: wheight},
			sizeLoading: { x: wwidth, y: wheight },
			classWindow: 'tjlms-modal',
			classOverlay: 'tjlms_lesson_screen_overlay',
			onClose: function() {
				//~ window.parent.document.location.reload(true);
			}
		});
	}


	/* Function to load the loading image. */
	function validatevideokpoint(formid,format,subformat,media_id)
	{
		var res = {check: 1, message: ""};

		var format_lesson_form = jQuery("#lesson-format-form_"+ formid);

		if(media_id == 0)
		{
			if (!jQuery("#lesson_format #" + format + " [name='lesson_format[kpoint][url]']",format_lesson_form).val())
			{
				res.check = '0';
				res.message = "<?php echo Text::_('PLG_TJVIDEO_KPOINT_URL_MISSING');?>";
			}
		}

		return res;
	}

	/*Function to hit url*/
	function hitURL(url)
	{
		jQuery.ajax(
		{
			url: url,
			beforeSend: function(){
			},
			success: function(result)
			{
				if (result)
				{
					window.open(result,'_blank');
				}
			}
		});
	}

	/*Function to publish video*/
	function PublishVideo(thisbtn, url)
	{
		jQuery.ajax(
		{
			url: url,
			beforeSend: function(){
				jQuery("#"+thisbtn).text('Processing');
			},
			success: function(result)
			{
				if (result == 1)
				{
					jQuery("#"+thisbtn).hide();
					//~ jQuery(".download-btn").show();
					jQuery(".download-btn").removeClass('hide');
					jQuery("#publish_message").text('Video published successfully.');
					publishLesson();
				}
			}
		});
	}

	/*Function to publish video*/
	function publishLesson()
	{
		jQuery.ajax({
			url:  '<?php echo Uri::root();?>index.php?option=com_tjlms&task=callSysPlgin&plgType=tjvideo&plgName=kpoint&plgtask=updateLessonState&callType=1&lesson_state=1&lesson_id=<?php echo $lesson->lesson_id;?>',
			success: function(response)
			{
				console.log(response);
				jQuery("#lesson-basic-form_" + window.thisbtnData  +" input[id=jform_state01]").attr('checked');
				jQuery("#lesson-basic-form_" + window.thisbtnData  +" label[for=jform_state01]").attr('class', 'active btn-success');
				jQuery("#lesson-basic-form_" + window.thisbtnData  +" input[id=jform_state11]").removeAttr('checked', 'checked');
				jQuery("#lesson-basic-form_" + window.thisbtnData  +" label[for=jform_state11]").removeAttr('class', 'active btn-danger');
			}
		});
	}
</script>
