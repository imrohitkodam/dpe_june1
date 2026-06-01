<?php

/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,TJVideo,cincopa
 *
 * @copyright   Copyright (C) 2005 - 2021 open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\Http\Http;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;

$subFormat = '';

if (!empty($lesson->sub_format))
{
	$subFormat              = $lesson->sub_format;
	$subformatSourceOptions = explode('.', $subFormat);
	$sourceOption           = $subformatSourceOptions[1];
	$source                 = $lesson->source;

	if (!empty($sourceOption))
	{
		$path     = Uri::root() . 'media/com_tjlms/lessons/' . $lesson->source;
		$filepath = $lesson->org_filename;
		$filename = basename($filepath);
	}

	$fileName = substr($source, strpos($source, "0/") + 2);
	echo 'File : ' . $fileName;
}
?>
<div class="container-fluid "><?php
	if (!empty($sourceOption))
	{
		?>
		<div class="row-fluid">
			<?php
			$status = $statusRs[0]['status'];
			$statusPublish = $statusRs[0]['published_flag'];

			if (isset($status) && $status == 'processing'&&($statusRs[0]['published_flag'] == ''||$statusRs[0]['published_flag'] == false))
			{
					echo '<div class=" span12 alert alert-warning">' . Text::_('PLG_TJVIDEO_CINCOPA_UPLOADED_VIDEO_INPROCESS') . '</div>';
			}
			elseif (isset($statusRs[0]['status']) == 'ready' && ($statusPublish == '' || $statusPublish == false))
			{
				echo '<div class="span12 alert alert-warning" id="publish_message">' . Text::_('COM_TJLMS_CREATE_VIDEO_CPINT_PUBLISH_MSG') . '</div>';
			}
			else
			{
				echo '<div class="span12"></div>';
			} ?>
		</div><?php
	} ?>
	<div class="row-fluid cincopa-border">
		<div class="span2">
			<img class="cincopa_href" src="<?php echo $statusRs[0]['thumbnail_url'];?>" alt="">
		</div>
		<div class="span9 cincopa-det">
			<?php
			if (!empty($sourceOption))
			{
			?>
				<div class="cincopa-title cincopa_text"><?php echo (isset($statusRs[0]['displayname'])) ? $statusRs[0]['displayname'] : ""; ?></div>
			<?php
			}
			else
			{
				?>
				<div class="cincopa-title cincopa_text"><?php echo Text::_("PLG_TJVIDEO_CINCOPA_DEFULT_VIDEO_NAME");?></div>
				<div class="cincopa_ownername">&nbsp;</div>
				<?php
			} ?>
			<div class="cincopa-selectVideo" id="video_textarea"><?php
				$link = Uri::root()
				. 'index.php?option=com_tjlms&task=callSysPlgin&plgType=tjvideo&plgName=cincopa&plgtask=getHtml&callType=1&lesson_id='
				. $lesson_id; ?>
				<a class="btn btn-primary" onClick="openListVideoss(this,'<?php echo $link;?>');"
				rel="{size: {x: 700, y: 300}}"><i class="icon-list icon-white"></i>
					<?php echo Text::_("COM_TJLMS_VIDEO_CPINT_BTN");?>
				</a>
				<input type="hidden" id="video_url" value="<?php echo $lesson->source;?>" 
				class="cincopa_video input-block-level" cols="50" rows="2" name="lesson_format[cincopa][url]" >
			</div>
			<div class="span4 cincopa-selectVideo" id="video_creater" ><?php
				// Trigger all sub format  video plugins method that renders the video player

				$dispatcher = JDispatcher::getInstance();
				PluginHelper::importPlugin('tjvideo', 'cincopa');
				$result = $dispatcher->trigger('getCreateVideoHtml');
				$linkx = $result[0];

				if ($this->params->get('create_video'))
				{
					?>
				<a class="btn btn-primary" href="<?php echo $linkx;?>" onclick="openVideoCreater(this)" target="_blank">
					<i class="icon-pencil icon-white"></i><?php echo Text::_("COM_TJLMS_CREATE_VIDEO_CPINT_BTN");?>
				</a><?php
				}
				?>
			</div>
			<div class="span4 cincopa-selectVideo"><?php
				if (!empty($sourceOption))
				{
					$status = $statusRs[0]['status'];
					$statusPublish = $statusRs[0]['published_flag'];

					if (isset($status) && $status == 'ready' && (isset($statusPublish) && $statusPublish == '' || $statusPublish == false))
					{
						$link = Uri::root()
						. 'index.php?option=com_tjlms&task=callSysPlgin&plgType=tjvideo&plgName=cincopa&plgtask=getKapsuleStatusUpdate&callType=1&source='
						. $source; ?>
						<a class="btn btn-success" id="publish_button" onclick="PublishVideo(this.id, '<?php echo $link;?>')">
							<i class="icon-plus icon-white"></i>
							<?php echo Text::_("COM_TJLMS_CREATE_VIDEO_CPINT_PUBLISH") ?>
						</a>
						<?php
					}
				}
						?>
			</div>
		</div>
	</div>
</div>
<input type="hidden" id="subformatoption" name="lesson_format[cincopa][subformatoption]" value="url"/>

<?php
$document = Factory::getDocument();
$document->addScript(Uri::root(true) . '/plugins/tjvideo/cincopa/assets/js/cincopa.js');
