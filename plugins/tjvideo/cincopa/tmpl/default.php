<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,TJVideo,cincopa
 *
 * @copyright   Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
$var = json_decode($vars, true);

//  This is gallery section . List of gallery Showin Here
if (is_array($var) && isset($var['items_data']['items_count']) && isset($var['galleries']))
{
	if ((int) $var['items_data']['items_count'] < 0)
	{
		echo Text::_('PLG_TJVIDEO_CINCOPA_LIST_NOT_FOUND');
	}
	else
	{
		$input     = Factory::getApplication()->input;
		$lesson_id = $input->get('lesson_id', '0', 'INT');
		$formId    = $input->get('form_id', ' ', 'STRING');
		$maxSearch = $this->params->get('max_search');
		?>
		

		<input type="text" name="rooturl" id='rooturl' style="display: none;"hidden="hidden" value="<?php echo Uri::root(); ?>">
		<div id="appendHtml<?php echo $lesson_id;?>">
			<div class="modal-header" id="modal-header">
				<button type="button" class="close" onclick="window.parent.SqueezeBox.close();document.location.reload();" 
				data-dismiss="modal" aria-hidden="true">×</button>
				<h3><?php echo Text::_('PLG_TJVIDEO_CINCOPA_LIST'); ?>
			</div>
			<table class="table table-striped" id="categoryList" style="position: relative;">
				<thead>
				<tr><td colspan="2"><div class="btn-wrapper input-append">
					<input type="text" name="filter" id="filter_search" value="" placeholder="Search">
					<button type="submit" class="btn hasTooltip" title="" data-original-title="Search" onclick="getHtml('search');">
						<i class="icon-search"></i>
					</button>
					<button type="submit" class="btn" onclick="getHtml('clear');">
						<i class="icon-remove"></i>
					</button>
					

				</div>
				</td>
				</thead>
				<!-- <tbody> -->
			<?php
			if (empty($var['galleries']))
			{
				?>
				<tr>
					<td colspan="4"><?php Text::_('COM_TJLMS_CINCOPA_PLG_RECORD_NOT_FOUND');?></td></td>
				</tr>
			<?php 
			}
			else
			{
				?>
			<!-- </tbody> -->
			<tfoot style="display:none;">
			<tr><td colspan="4"><img src="<?php  echo Uri::root() . 'media/com_tjlms/images/loader/loader.gif'; ?>" style="width: 5%; height: 5%;"></td></tr></tfoot>

			</table>
				<?php

				foreach ($var['galleries'] as $key => $value)
				{
					?>
					<div class="controls controls-row cincopa_video_tr">
						<div class="span2 video_img" style="width: 20px;">
							<?php
						$fid = $value['fid'];
						$link = Uri::root() . 'index.php?option=com_tjlms&task=callSysPlgin&plgType=tjvideo&plgName=cincopa&plgtask=getHtml&callType=1&lesson_id='
						. $lesson_id . '&fid=' . $fid . '&form_id=' . $formId; ?>
						<img src="<?php  echo Uri::root()
								. '/media/com_tjlms/images/default/icons/video.png';?>" class="img-polaroid">
						</div> 
						<div class="span4" >
							<div class="video_link" style="font-size: 16px;">
								<a href="javascript:void(0)" onClick="openListVideos_all(this,'<?php echo $link;?>');"><strong>
									<?php echo $value['name'];?></strong>
								</a>
							</div>
							<br>
							<div class="video_desc">
								<?php echo $value['description'];?>
							</div>
						</div>
					</div>
					<br>
					<?php
				}
					?>
				<input type="hidden" id="totalcount" value="<?php echo $var['items_data']['items_count']; ?>">
				<input type="hidden" id="limit" value="<?php echo $var['items_data']['page']; ?>">

				<?php
			}
			?>
		</div>
		<h3 id="load" hidden="hidden"><?php Text::_('PLG_TJVIDEO_CINCOPA_LOADING');?></h3>

		<?php
	}
}

	elseif(!isset($var['galleries']))
	{
	//  This  section Fetch List of videos for a particular gallery .

		if ((int) $var['folder']['items_data']['items_count'] < 0)
		{
				echo Text::_('PLG_TJVIDEO_CINCOPA_LIST_NOT_FOUND');
		}
		else
		{
			$input     = Factory::getApplication()->input;
			$lesson_id = $input->get('lesson_id', '0', 'INT');
			$formId   = $input->get('form_id', ' ', 'STRING');
			$maxSearch = $this->params->get('max_search');
			?>

		<div id="appendHtml<?php echo $lesson_id;?>">
			<div class="modal-header" id="modal-header1">
				<button type="button" class="close" onclick="window.parent.SqueezeBox.close();document.location.reload();"
				data-dismiss="modal" aria-hidden="true">×</button>
				<h3><?php echo "List of Videos" ?>
			</div>

			<table class="table table-striped" id="categoryList" style="position: relative;">
				<thead>
				 <tr>
				</thead>
				<!-- <tbody> -->

			<?php 
			if (isset($var['galleries']))
			{
				?>
				<tr>
					<td colspan="4"><?php Text::_('COM_TJLMS_CINCOPA_PLG_RECORD_NOT_FOUND');?></td>
				</tr>
			<?php 
			}
			else
			{
				?>
			<!-- </tbody> -->
			<tfoot style="display:none;">
			<tr><td colspan="4"><img src="<?php echo Uri::root() . 'media/com_tjlms/images/loader/loader.gif';?>"style="width: 5%; height: 5%;"></td></tr></tfoot>
			</table>
			<button id= "back" class="controls controls-row button"style="float: right;">Back</button>
				<?php
				for ( $i = 0; $i < count($var['folder']['items']); $i++)
				{
					?>
					<div class="controls controls-row cincopa_video_tr">
						<div class="span2 video_img">
							<a href="#"onclick="parent.bindCincopa('<?php echo $formId;?>'
							,'<?php echo $var['folder']['items'][$i]['versions']['original']['url'];?>'
							,'<?php echo $var['folder']['items'][$i]['filename']; ?>'
							,'<?php echo $var['folder']['items'][$i]['thumbnail']['url']; ?>'
							,'<?php echo $var['folder']['items'][$i]['description']; ?>')" >
							<img src="<?php echo $var['folder']['items'][$i]['versions']['jpg_600x450']['url'];?>" class="img-polaroid" height="200" width="200">
						</a>
						</div>
						<div class="span4"  >
							<div class="video_link" style="font-size: 16px;">
								<a href="#"onclick="parent.bindCincopa('<?php echo $formId;?>'
							,'<?php echo $var['folder']['items'][$i]['versions']['original']['url'];?>'
							,'<?php echo $var['folder']['items'][$i]['filename']; ?>'
							,'<?php echo $var['folder']['items'][$i]['thumbnail']['url']; ?>'
							,'<?php echo $var['folder']['items'][$i]['description']; ?>')" >
								<strong><?php $data = $var['folder']['items'][$i]['versions']['original']['url'];
								$name = substr(strrchr($data, '/'), 1);
								echo $name;
								?></strong>
							</a>
							</div>
							<div class="video_desc">
								<?php echo $var['folder']['items'][$i]['description'];?>
							</div>
						</div>
					</div>
					<?php
				}
					?>
				<input type="hidden" id="totalcount" value="<?php echo $var['counts']['videos']; ?>">
				<input type="hidden" id="limit" value="<?php echo count($var['list']); ?>">
				<?php
			}
			?>
		</div>
		<?php
		}
	}
else
{
	if (isset($var['error']))
	{
		echo '<div class="alert alert-danger" role="alert">' . $var['error']['message'] . '</div>';
	}
	elseif(is_string($var))
	{
		echo '<div class="alert alert-danger" role="alert">' . $var . '</div>';
	}
	else
	{
		if ($var == 1)
		{
			echo '<div class="alert alert-danger" role="alert">
  <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
  <span class="sr-only">' . Text::_('COM_TJLMS_VIDEO_CPINT_PARAMETER_NOT_FOUND') . '</span></div>';
		}
		else
		{
			echo '<div class="alert alert-danger" role="alert">
  <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
  <span class="sr-only">' . Text::_('COM_TJLMS_VIDEO_CPINT_API_NOT_FOUND') . '</div>';
		}
	}
}?>
		<?php $link1 = Uri::root()
			. 'index.php?option=com_tjlms&task=callSysPlgin&plgType=tjvideo&plgName=cincopa&plgtask=getHtml&callType=1&lesson_id='
			. $lesson_id; ?>
		<input type="text" name=""id='link1' value="<?php echo $link1;?>"style="display: none;">
		<input type="text" name="lesson" id='lesson' style="display: none;"hidden="hidden" value="<?php echo $lesson_id; ?>">
		<input type="text" name="formId"  id="formId" style="display: none;"hidden='hidden' value="<?php echo $formId; ?>">
		<input type="text" name="url1" id = 'url1' value="index.php?option = com_tjlms&task =callSysPlgin&plgType=tjvideo&plgName=
		cincopa&plgtask=getKapsuleData&callType=1&source="
		 style="display: none;">

		<input type="text" name="url2" id = 'url2' value="index.php?option=com_tjlms&task=callSysPlgin&plgType=tjvideo&plgName=
		cincopa&plgtask=updateLessonState&callType=1&lesson_state=0&lesson_id=
		<?php echo $lesson->lesson_id?>;" style="display: none;">
		<input type="text" value= 'index.php?option=com_tjlms&task=callSysPlgin&plgType=tjvideo&callType=1&plgName=cincopa&plgtask=getHtml&lesson_id='
		name="loadmore" id = 'loadmore' style="display: none;">
<?php
$document = Factory::getDocument();
$document->addScript(Uri::root(true) . '/plugins/tjvideo/cincopa/assets/js/cincopa.js');
?>
