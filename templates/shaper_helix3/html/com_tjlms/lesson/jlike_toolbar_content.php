<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.

defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Factory;

HTMLHelper::script('components/com_jlike/assets/scripts/jlike-assignments.js');
HTMLHelper::_('jquery.token');

?>
<div>
<!--DIV CONTAINING DRWAER-->
<div id="lists" class="toolbar-content">
	<small class="text-muted">
		<span><?php echo Text::_('COM_TJLMS_LIST_HELP_TEXT'); ?></span>
	</small>
	<div class="clearfix"></div>
	<?php
		
		PluginHelper::importPlugin('content');
		$result =Factory::getApplication()->triggerEvent('showLists',array('com_tjlms.lesson',$lesson_data->id,$lesson_data->title));
		if(!empty($result))
		echo $result[0];
	?>
</div>
<div id="notes" class="toolbar-content">
	<small class="text-muted">
		<span><?php echo Text::_('COM_TJLMS_NOTE_HELP_TEXT'); ?></span>
	</small>
	<div class="clearfix"></div>
		<?php
		
		PluginHelper::importPlugin('content');
		$result =Factory::getApplication()->triggerEvent('showNotes',array('com_tjlms.lesson',$lesson_data->id,$lesson_data->title));
		if(!empty($result))
		echo $result[0];
	?>
</div>
<div id="comments" class="toolbar-content">
	<?php
		
		PluginHelper::importPlugin('content');
		// $result =Factory::getApplication()->triggerEvent('showComments',array('com_tjlms.lesson',$lesson_data->id,$lesson_data->title));
		$result =Factory::getApplication()->triggerEvent('showAllComments',array('com_tjlms.lesson',$lesson_data->id,$lesson_data->title));
		if(!empty($result))
		echo $result[0];
	?>
</div>
<div id="interaction" class="toolbar-content">
<?php
	if (!empty($jLikeInteractions) && $lessonInteractionFlag == true)
	{
		foreach($jLikeInteractions  as $jLikeInteraction)
		{
			if (!empty($jLikeInteraction->content))
			{
				echo $jLikeInteraction->content;
			}
		}
	}
?>
</div>

<div id="assignment" class="toolbar-content">
	<div
		data-assignment-content-id="<?php echo $lesson_data->id;?>"
		data-assignment-client="com_tjlms.lesson"
		data-for-assignment="assignment"
		class="initassignment">
	</div>

	<div class="alert alert-info assignment-todos-unabletoload hide" role="alert">
		<?php echo Text::_('COM_JLIKE_ASSIGNMENT_UNABLETOLOAD'); ?>
	</div>
</div>

<?php if ($this->allowAssocFiles == 1): ?>

	<div class="associatefiles toolbar-content" id="associatefiles">
		<small class="text-muted">
			<span><?php echo Text::_('COM_TJLMS_ASSOC_FILES_HELP_TEXT'); ?></span>
		</small>
		<div class="clearfix"></div>
		<hr class="hr hr-condensed">

	<?php if (!empty($this->lesson_typedata->associateFiles)): ?>

		<?php foreach($this->lesson_typedata->associateFiles as $assocFile): ?>

			<div class="assocfilecontainer">
				<div class="span1">
					<i class="icon-file"></i>
				</div>
				<div class="span10">
					<?php echo $assocFile->filename; ?>
				</div>
				<div class="span1">
					<?php
						$downloadUrl = JURI::root() .'index.php?option=com_tjlms&task=lesson.downloadMedia&mid=' . $assocFile->media_id;

					?>
					<a target="_blank" href="<?php echo $downloadUrl; ?>" title="<?php echo Text::_('COM_TJLMS_ASSOC_FILES_DOWNLOAD'); ?>"><i class="fa fa-download"></i></a>
				</div>
			</div>
			<div class="clearfix"></div>
				<hr class="hr hr-condensed">
			<div class="clearfix"></div>

		<?php endforeach; ?>

	<?php else: ?>

		<div class="alert alert-warning">
			<?php echo Text::_('COM_TJLMS_NO_ASSOCIATE_FILES');?>
		</div>

	<?php endif; ?>
	</div>

<?php endif; ?>
</div>
