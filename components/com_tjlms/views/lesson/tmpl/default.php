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

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Plugin\CMSPlugin;

jimport('joomla.html.pane');

$options['relative'] = true;
JHtml::stylesheet('com_tjlms/jlike.css', $options);
JHtml::stylesheet('media/techjoomla_strapper/bs3/css/bootstrap.css');
JHtml::_('bootstrap.framework');

$jinput = Factory::getApplication()->input;
$jinput->set('tmpl', 'component');

$close = $jinput->get('close', '', 'INT');

// If invalid url, throw error
if ($this->inValidUrl == 1)
{
	?>
		<div class="alert alert-danger">
			<span><?php echo Text::_('COM_TJLMS_LESSON_INVALID_URL');?></span>
		</div>
	<?php
	return;
}

// Get lesson data
$lesson_data = $this->lesson;
// If invalid url, throw error
if ($this->usercanAccess['access'] == 0)
{
	?>
		<div class="alert alert-danger">
			<span><?php echo $this->usercanAccess['msg'];	?></span>
			<span><?php echo Text::sprintf('COM_TJLMS_LESSON_CLICK_COURSE_LINK', $this->returnUrl);?></span>
		</div>
	<?php
	return;
}

$lessonUrl = "index.php?option=com_tjlms&view=lesson&lesson_id=" . $lesson_data->id . "&tmpl=component&lessonscreen=1";

if ($this->course_id)
{
	$lessonUrl .= "&cid=" .$this->course_id;
}

$lesson_url = $this->tjlmshelperObj->tjlmsRoute($lessonUrl ,false);

$params = ComponentHelper::getParams('com_tjlms');

// Jlike toolbar position
$show_toolbar_at_top = $params->get('tjlms_toolbar_option','1');

$toolbarClass = "fixed-top";


if ($show_toolbar_at_top == 0)
{
	$toolbarClass = "fixed-bottom";
}

$toolbar_content_class = 'lesson-right-panel';

PluginHelper::importPlugin('content');
$jLikeInteractions = Factory::getApplication()->triggerEvent('getLessonInteractions', array($this->lesson_id));

// Get jlike toolbar
$jlike_toolbar_file = $this->tjlmshelperObj->getViewpath('com_tjlms', 'lesson','jlike_toolbar');
ob_start();
include($jlike_toolbar_file);
$toolbar_html = ob_get_contents();
ob_end_clean();

// Get jlike toolbar content
$jlike_toolbar_content_file = $this->tjlmshelperObj->getViewpath('com_tjlms', 'lesson','jlike_toolbar_content');
ob_start();
include($jlike_toolbar_content_file);
$toolbar_content_html = ob_get_contents();
ob_end_clean();

if ($this->mode == 'preview') {

	HTMLHelper::stylesheet('media/techjoomla_strapper/bs3/css/bootstrap.css');
	HTMLHelper::_('bootstrap.framework');

}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<!-- Container div-->
<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?> com_tjlms_content tjBs3" id="<?php echo $this->mode;?>">
	<div class="container-fluid">
		<div class="row tjlms-lesson" data-js-attr="tjlms-lesson">

		<?php if($this->mode != 'preview'): ?>

			<div class="<?php echo $toolbarClass;?>">
				<?php echo $toolbar_html; ?>
			</div>

		<?php elseif($this->mode == 'preview' && $close !== 0): ?>
			<div class="navbar-fixed-top" id="admin-close-button">
				<button type="button" class="close" onclick="tjLmsCommon.closePopup();"; data-dismiss="modal" aria-hidden="true"><i class="fa fa-close"></i></button>
			</div>
		<?php endif; ?>

		<?php $playList = 0 ; ?>

		<?php if ($this->showPlaylist == 1 && $this->mode != 'preview') : ?>
				<?php $playList = 1 ; ?>
		<?php endif; ?>

		<div class="tjlms-lesson__playlist-container hidden-xs col-sm-3 hidden"  data-js-attr="lesson-playlist">
			<?php
				if ($playList):
					echo $this->loadTemplate('playlist');
				endif;
			?>
		</div>

		<div class="tjlms_lesson__player tjlms-lesson-player col-xs-12 col-sm-12" data-js-attr="lesson-player">

		<?php if($this->askforinput	== 1): ?>

			<div id="resumeWindow" class="center text-center mt-10">
				<div class="well" id="askforattempt">

					<span class="help-block"><?php echo Text::_('COM_TJLMS_INCOMPLETE_LAST_ATTEMPT_MSG'); ?>
						<?php

						if($lesson_data->format!='scorm' && $lesson_data->format!='tjscorm' && $lesson_data->format!='textmedia' && $lesson_data->format!='externaltool')
						{
							$lang_constant_toshow	=	"COM_TJLMS_INCOMPLETE_LAST_ATTEMPT_STATUS_".$lesson_data->format;
							 echo Text::sprintf($lang_constant_toshow, $this->lastattempttracking_data->currentPositionFormat, $lesson_data->title, $this->lastattempttracking_data->totalContentFormat);
						}

						?>
					</span>

					<div class="clearfix"></div>

					<div class="container-fluid">
						<div class="row">
							<div class="col-xs-6 col-sm-6 text-right">
								<input type="button" name="new" value="<?php echo Text::_('COM_TJLMS_NEW_ATTEMPT') ?>" class="btn btn-default btn-medium" onclick="askforaction('start','<?php echo $lesson_data->id; ?>','<?php echo $lesson_url?>','<?php echo $this->attempt; ?>','<?php echo $lesson_data->format; ?>');">
							</div>
							<div class="col-xs-6 col-sm-6 text-left">
								<input type="button" id="old" name="old" value="<?php echo Text::_('COM_TJLMS_CONTINUE_OLD') ?>" class="btn btn-default btn-medium" onclick="askforaction('resume','<?php echo $lesson_data->id; ?>','<?php echo $lesson_url?>','<?php echo $this->attempt; ?>','<?php echo $lesson_data->format; ?>');">
							</div>
						</div>
					</div>
				</div><!--askforattempt ENDS-->
			</div><!-- resumeWindow ENDS -->

		<!-- If resume window... return from here-->
			<?php else: ?>

				<?php echo $this->loadTemplate(strtolower($lesson_data->format));	?>
		<?php endif; ?>

			</div>

			<div class="tjlms-lesson__toolbar-content col-xs-12 col-sm-4 p-15 display-none" data-js-attr="lesson-toolbar-content">

				<!-- If toolbar content position is at the bottom-->
				<?php if($this->mode != 'preview' && $lesson_data->format != 'tmtQuiz'): ?>
						<?php echo $toolbar_content_html; ?>
				<?php endif; ?>

			</div>

		</div>
	</div>
</div>

<script>
<?php
	Text::script('COM_TJLMS_LESSON_CONFIRM_BOX');
?>
	var root_url	=	"<?php echo JURI::base();?>";
	var launchMode =  "<?php echo $this->mode?>";
	var launchLessonFullScreen =  "<?php echo $this->launch_lesson_full_screen;?>";
	var returnUrl =   "<?php echo $this->returnUrl;?>";
	var showLessonPlaylist =   "<?php echo $playList;?>";
	var openModuleId = "<?php echo $this->openModuleId;?>"
	var lessonFormat = "<?php echo $lesson_data->format; ?>"

	if (lessonFormat == 'htmlzips')
	{
		jQuery(document).ready(function () {
			jQuery('#html_object').attr('loading', 'eager');
		});
	}

	tjlms.lesson.init(openModuleId);

</script>
