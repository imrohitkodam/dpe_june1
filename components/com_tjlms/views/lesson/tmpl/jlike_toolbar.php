<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;

?>
<?php $format = array('quiz', 'exercise', 'feedback');?>
<?php $resumeWindowClass = ' '; ?>

<?php if (in_array($lesson_data->format, $format) || $this->askforinput	== 1):
		$resumeWindowClass = 'resumeWindowPage';
endif; ?>

<div id="jlikeToolbar" class="container-fluid <?php echo $resumeWindowClass;?>">
	<div class="row">
		<div class="no-gutters">
		<?php if ($this->showPlaylist == 1 && $this->mode != 'preview') : ?>
			<span data-js-attr="tjlms-lesson__playlist-toggle" class="hidden-xs playlist-toggle toolbar_buttons text-center font-bold pull-left">
				<i class="playlist__close-icon fa fa-angle-double-right display-none" title="<?php echo Text::_('COM_TJLMS_LESSON_SHOW_PLAYLIST'); ?>" data-js-id="playlist-open"></i>
				<i class="playlist__open-icon fa fa-angle-double-left display-none" title="<?php echo Text::_('COM_TJLMS_LESSON_HIDE_PLAYLIST'); ?>" data-js-id="playlist-hide"></i>
			</span>
		<?php endif; ?>
			<div data-js-attr="tjlms-lesson__toolbar-container">
				<span class="ml-10 pull-left font-bold jlike-container">
					<?php if (!empty($this->jLikepluginParams) && $this->jLikepluginParams->get('allow_like') == 1){ ?>
						<?php
							PluginHelper::importPlugin('content');
							$result = Factory::getApplication()->triggerEvent('onShowlikebuttonforlesson',array('com_tjlms.lesson',$lesson_data->id,$lesson_data->title));
							if(!empty($result))
							echo $result[0];
						?>
					<?php } ?>
				</span>

				<div class="text-right jlikeToolbar__buttons" id="jlike_toolbar_buttons">

					<div class="d-inline-block">
						<span data-ref="jliketoolbar-menu" class="hidden toolbar_buttons">
							<i class="fa fa-bars"></i>
						</span>

					<?php if (1 != $this->olUser->guest && $this->allowAssocFiles == 1){ ?>
						<span data-ref="associatefiles" class="assocfilesbtn toolbar_buttons" data-js-attr="toolbar_buttons" title="<?php echo Text::_('COM_JLIKE_ASSOCIATE_FILE_LABEL');?>">
						<i class="fa fa-download"></i>
						</span>
					<?php } ?>

					<?php  if (!empty($this->jLikepluginParams) && $this->jLikepluginParams->get('allow_user_lables') == 1){ ?>
						<span data-ref="lists" class="toolbar_buttons" data-js-attr="toolbar_buttons" title="<?php echo Text::_('COM_JLIKE_LIST_LABEL');?>">
							<i class="fa fa-bookmark-o"></i>
						</span>
					<?php } ?>

					<?php if (!empty($this->jLikepluginParams) && $this->jLikepluginParams->get('allow_annotation') == 1){ ?>
						<span data-ref="notes" class="toolbar_buttons" data-js-attr="toolbar_buttons" title="<?php echo Text::_('COM_JLIKE_NOTES_LABEL');?>">
						  <i class="fa fa-file-text-o"></i>
						</span>
					<?php } ?>

					<?php if (!empty($this->jLikepluginParams) && $this->jLikepluginParams->get('allow_comments') == 1){ ?>
						<span data-ref="comments" class="toolbar_buttons" data-js-attr="toolbar_buttons" title="<?php echo Text::_('COM_JLIKE_COMMENTS_LABEL');?>">
							<i class="fa fa-comments"></i>
							<?php
								if ($this->comments_count)
								{
									?>
									<small id="total_comments">
										<?php echo $this->comments_count; ?>
									</small>
									<?php
								}
							?>
						</span>
					<?php } ?>

					<?php
						if (!empty($jLikeInteractions))
						{
							foreach ($jLikeInteractions  as $jLikeInteraction)
							{
							?>
								<span data-ref="<?php echo $jLikeInteraction->ref;?>" class="toolbar_buttons" data-js-attr="toolbar_buttons"
								title="<?php echo Text::_('Show Interaction');?>">
									<i class="fa fa-dropbox"></i>
								</span>
							<?php
							}
						}
					?>
						<span data-js-attr="jlikeToolbar-close" class="toolbar_buttons closeBtn"
						title="<?php echo Text::_('COM_TJLMS_CLOSE');?>" data-js-id="test-close">
							<i class="fa fa-close"></i>
						</span>
					</div>
				</div>

			</div>
		</div>
	</div>
</div>
