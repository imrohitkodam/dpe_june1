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
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;


$viewInteractions = true;

if (!$this->olUser->authorise('core.manageall', 'com_cluster'))
{
	JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

	// Get cluster id associated with document
	Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpe/tables');
	$clustertableInstance = Table::getInstance('TjlmsClusterXref', 'DpeTable');
	$clustertableInstance->load(array('lesson_id' => $lesson_data->id));

	$viewInteractions = RBACL::check($this->olUser->id, 'com_cluster', 'core.view.interactions', 'com_tjlms', $clustertableInstance->cluster_id);
}

JLoader::import('models.contentform', JPATH_SITE . '/components/com_tjlms');

// Get content id
$data = array();
$data['element_id'] = $lesson_data->id;
$data['element'] = 'com_tjlms.lesson';
$contentformModel = new JlikeModelContentForm;
$contentId = $contentformModel->getContentID($data); 

// Check todo available for logged in user
// JLoader::import('models.recommendation', JPATH_SITE . '/components/com_jlike');
// $JLikeRecommendationModel = new JlikeModelRecommendations;

BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_jlike/models', 'JlikeModel');
$JLikeRecommendationModel = BaseDatabaseModel::getInstance('Recommendations', 'JlikeModel', array("ignore_request" => 1));


$JLikeRecommendationModel->setState("content_id", $contentId);
$JLikeRecommendationModel->setState("assigned_to", $this->olUser->id);
$isAssigned = $JLikeRecommendationModel->getItems();


?>
<?php $format = array('quiz', 'exercise', 'feedback');?>
<?php $resumeWindowClass = ' ';	?>
<?php if (in_array($lesson_data->format, $format) || $this->askforinput	== 1):
		$resumeWindowClass = 'resumeWindowPage';
endif; ?>

<div id="jlikeToolbar" class="container-fluid <?php echo $resumeWindowClass;?>">
	<div class="row">
		<div class="no-gutters">
		<?php if ($this->showPlaylist == 1 && $this->mode != 'preview') : ?>
			<span data-js-attr="tjlms-lesson__playlist-toggle" class="hidden-xs playlist-toggle toolbar_buttons text-center font-bold pull-left">
				<i class="playlist__close-icon fa fa-angle-double-right display-none" title="<?php echo Text::_('COM_TJLMS_LESSON_SHOW_PLAYLIST'); ?>"></i>
				<i class="playlist__open-icon fa fa-angle-double-left" title="<?php echo Text::_('COM_TJLMS_LESSON_HIDE_PLAYLIST'); ?>"></i>
			</span>
		<?php endif; ?>
			<div data-js-attr="tjlms-lesson__toolbar-container">
				<span class="ml-10 pull-left font-bold jlike-container">
					<?php if (!empty($this->jLikepluginParams) && $this->jLikepluginParams->get('allow_like') == 1){ ?>
						<?php
							
							PluginHelper::importPlugin('content');
							$result = Factory::getApplication()->triggerEvent('showlikebuttonforlesson',array('com_tjlms.lesson',$lesson_data->id,$lesson_data->title));
							if(!empty($result) && $this->course_id)
							echo $result[0];
						?>
					<?php } ?>
				</span>

				<div class="text-right jlikeToolbar__buttons" id="jlike_toolbar_buttons">

					<div class="d-inline-block">
						<span data-ref="jliketoolbar-menu" class="hidden toolbar_buttons">
							<i class="fa fa-bars"></i>
						</span>

					<?php if ($this->course_id && 1 != $this->olUser->guest && $this->allowAssocFiles == 1){ ?>
						<span data-ref="associatefiles" class="assocfilesbtn toolbar_buttons" data-js-attr="toolbar_buttons" title="<?php echo Text::_('COM_JLIKE_ASSOCIATE_FILE_LABEL');?>">
						<i class="fa fa-download"></i>
						</span>
					<?php } ?>

					<?php  if ($this->course_id && !empty($this->jLikepluginParams) && $this->jLikepluginParams->get('allow_user_lables') == 1){ ?>
						<span data-ref="lists" class="toolbar_buttons" data-js-attr="toolbar_buttons" title="<?php echo Text::_('COM_JLIKE_LIST_LABEL');?>">
							<i class="fa fa-bookmark-o"></i>
						</span>
					<?php } ?>

					<?php if ($this->course_id && !empty($this->jLikepluginParams) && $this->jLikepluginParams->get('allow_annotation') == 1){ ?>
						<span data-ref="notes" class="toolbar_buttons" data-js-attr="toolbar_buttons" title="<?php echo Text::_('COM_JLIKE_NOTES_LABEL');?>">
						  <i class="fa fa-file-text-o"></i>
						</span>
					<?php } ?>

					<?php if ($this->course_id && !empty($this->jLikepluginParams) && $this->jLikepluginParams->get('allow_comments') == 1){ ?>
						<span data-ref="comments" class="toolbar_buttons" data-js-attr="toolbar_buttons" title="<?php echo Text::_('COM_JLIKE_COMMENTS_LABEL');?>">
							<i class="fa fa-comments"></i>
						</span>
					<?php } ?>

					<?php
					// Show interaction button only for assigned users excluding staff
					if ($isAssigned && $viewInteractions)
					{
						if (!empty($jLikeInteractions) && $lessonInteractionFlag == true)
						{
							foreach ($jLikeInteractions  as $jLikeInteraction)
							{
							?>
								<span data-ref="<?php echo $jLikeInteraction->ref;?>" class="toolbar_buttons <?php if(!$this->course_id && (strpos($jLikeInteraction->content, '<form') !== false)){ echo ' active';} ?>" data-js-attr="toolbar_buttons"
							title="<?php echo Text::_('COM_TJLMS_INTERACTION_HEAD_DESC');?>">
										<?php echo Text::_('COM_TJLMS_INTERACTION_HEAD');?>
								</span>
							<?php
							}
						}
					}
					?>
						<!-- Check RBACL for view interactions -->
						<?php
						if ($viewInteractions && !$this->course_id)
						{?>
							<span data-ref="assignment" class="toolbar_buttons <?php echo empty($isAssigned) ? "assignBtn" : '';?> " data-js-attr="toolbar_buttons">
							<?php echo Text::_('COM_TJLMS_ASSIGNED_USERS_HEAD');?>
							</span>
						<?php
						}
						?>
						<span data-js-attr="jlikeToolbar-close" class="toolbar_buttons closeBtn" title="<?php echo Text::_('COM_TJLMS_CLOSE');?>">
							<?php echo Text::_('COM_TJLMS_CLOSE');?>
						</span>
					</div>
				</div>

			</div>
		</div>
	</div>
</div>
