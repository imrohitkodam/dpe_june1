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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

// Depends on jQuery UI
//HTMLHelper::_('jquery.ui', array('core', 'sortable'));
HTMLHelper::_('bootstrap.modal', 'lessonTypeModal');

$input = Factory::getApplication()->input;

foreach($this->moduleData as $moduleData)
{
	$modUnpubClass = '';

	if ($moduleData->state != 1)
	{
		$modUnpubClass = 'modUnpublish';
	}
?>
	<!--Here the LI represents all modules in a particular course-->
	<li id="modlist_<?php	echo	$moduleData->id;	?>" class=" module_lessons mod_outer mb-20" data-js-id='course-module' data-js-itemid="<?php echo	$moduleData->id;?>">
		<div class="row tjlms_module <?php echo $modUnpubClass; ?>" >
			<div class="content-li   col-md-10">
				<i class="icon-menu moduleSortingHandler" title="<?php echo Text::_('COM_TJLMS_SORT_MODULE'); ?>"></i>
				<i class="icon-book icon-white"></i><span class="tjlms_module_title"><?php	echo $moduleData->name;	?></span>
			</div>
			<div class="tjlms-actions btn-group non-accordian col-md-2" >
				<div class="module-functionality-icons" >
					<input type="hidden" data-js-id="module-state" value="<?php echo $moduleData->state;?>"/>
					<!--STATE MODULE BUTTON-->
					<a class="tjlms_module__changestate hide" data-js-id="change-module-state" title="<?php echo ($moduleData->state == 0) ? Text::_('COM_TJLMS_MODULE_CHANGE_STATE_PUBLISH') : Text::_('COM_TJLMS_MODULE_CHANGE_STATE_UNPUBLISH'); ?>">
						<i class="<?php echo ($moduleData->state == 1) ? 'icon-publish' : 'icon-unpublish';?>"></i>
					</a>

					<!--EDIT MODULE BUTTON-->
					<a class="tjlms_module__edit hide" title="<?php echo Text::_('COM_TJLMS_EDIT_MODULE'); ?>" data-js-id="edit-module">
						<span class="icon-edit"></span>
					</a>

					<!--DELETE MODULE BUTTON-->
					<a class="tjlms_module__delete hide" data-js-id="delete-module" title="<?php echo Text::_('COM_TJLMS_MODULE_DELET'); ?>">
						<span class="icon-trash"></span>
					</a>

				</div>
			</div>
		</div>
		<div class="module-edit-form editing" data-js-id="edit-module-form">
			<?php
				$moduleHtml='';
				$modId = $moduleData->id;
				$modName = $moduleData->name;
				$modState = $moduleData->state;
				$modImage = $moduleData->image;
				$modDescription = $moduleData->description;
				$courseId	= $this->course_id;
				$tjlmshelperObj	=	new comtjlmsHelper();
				$layout = $tjlmshelperObj->getViewpath('com_tjlms','modules','module','ADMIN','ADMIN');
				ob_start();
				include($layout);
				$moduleHtml.= ob_get_contents();
				ob_end_clean();
				echo $moduleHtml;
			?>
		</div>
		<!--UL containing all lessons of a modules-->
		<ul id="curriculum-lesson-ul_<?php echo $moduleData->id; ?>" class="LessonsInModule" data-js-id='lessons_container' class="connectedSortable curriculum-lesson-ul">
			<?php
			if(!empty($moduleData->lessons))
			{
				foreach($moduleData->lessons as $m_lesson)
				{
					$m_lesson_status_class	=	'unpublished';
					if(!$m_lesson->media_id)
						$m_lesson_status_class	=	'no_content';
					else if($m_lesson->state == 1)
						$m_lesson_status_class	=	'published';
					else
						$m_lesson_status_class	=	'unpublished';

				?>
					<!--LI for lessons-->
					<li id="lessonlist_<?php echo $m_lesson->id;?>" class="curriculum-lesson-li <?php echo $m_lesson_status_class;?>" onchange="sortLessons(this.id,<?php	echo $moduleData->id;	?>)" style="padding: 0.4em;" data-js-id="module-lesson" data-js-itemid="<?php echo $m_lesson->id;?>">
						<ul class="unstyled_list non-sortable-lesson-li">
							<li class="lesson_main_li non-sortable-lesson-li">
								<i class="icon-menu lessonSortingHandler" title="<?php echo Text::_('COM_TJLMS_SORT_LESSON'); ?>"></i>
								<?php if($m_lesson->format != ''){ ?>

									<img class="" alt="<?php echo $m_lesson->format; ?>" title="<?php echo $m_lesson->format; ?>" src="<?php echo Uri::root().'media/com_tjlms/images/default/icons/'.$m_lesson->format.'.png';?>"/>

								<?php }else{ ?>

									<img class="" src="<?php echo Uri::root().'media/com_tjlms/images/default/icons/noformat.png';?>"/>

								<?php } ?>
								<span class="title"><?php echo $m_lesson->title;?></span>
								<div class="tjlms-actions" style="float:right; ">
									<?php
										if (in_array($m_lesson->format, array('quiz', 'exercise', 'feedback')))
										{
											$editLink = "index.php?option=com_tmt&view=test&layout=edit&id=" . $m_lesson->media_source . "&lid=" . $m_lesson->id . "&cid=" . $m_lesson->course_id ."&mid=" . $m_lesson->mod_id;
										}
										else
										{
											$editLink = "index.php?option=com_tjlms&view=lesson&layout=edit&id=" . $m_lesson->id . "&cid=" . $m_lesson->course_id . "&mid=" . $m_lesson->mod_id;
										}

									?>
									<!--EDIT LESSON BUTTON-->
									<a class="tjlms_lesson__edit hide" title="<?php echo Text::_('COM_TJLMS_EDIT_LESSON');?>" href="<?php echo $editLink;?>">
										<span class="icon-edit"></span>
									</a>
									<!--DELETE LESSON BUTTON-->
									<a class="tjlms_lesson__delete hide" data-js-id="delete-lesson" title="<?php if($m_lesson->format != 'tmtQuiz') echo Text::_('COM_TJLMS_LESSON_DELET'); else echo Text::_('COM_TJLMS_QUIZ_DELET'); ?>">
										<span class="icon-trash"></span>
									</a>

								</div>
								<div style="clear:both"></div>
							</li>

						</ul>

					</li>
		<?php
				}
			}
			?>

		</ul><!--UI for lessons ends-->

		<div class="row module_lessons__actions">
			<?php
				$this->cid = $this->course_id;
				$this->mid = $moduleData->id;
			?>

			<ul class="list-group list-group-horizontal">
				<li class="list-group-item w-50">
					<span data-js-attr="add-lesson" data-bs-toggle="modal" data-bs-target="#lessonTypeModal<?php echo $this->mid;?>" class="action btn btn-add-lesson" title="<?php echo Text::_('COM_TJLMS_TITLE_ADD_LESSON'); ?>">

					<span class="icon-new icon-white mr-5" aria-hidden="true"></span>
					<?php echo Text::_('COM_TJLMS_TITLE_ADD_LESSON'); ?>
					</span>
				</li>

				<li class="list-group-item w-50">
					<span data-js-attr="add-existing-lesson" data-bs-toggle="modal" data-bs-target="#addExistingQuiz<?php echo $this->mid;?>" class="action btn btn-add-lesson" title="<?php echo Text::_('COM_TJLMS_ADD_EXISTQUIZ'); ?>">

						<span class="icon-new icon-white mr-5" aria-hidden="true"></span>
						<?php echo Text::_('COM_TJLMS_ADD_EXISTQUIZ'); ?>
					</span>

					<?php 
						$link = Route::_("index.php?option=com_tmt&view=tests&layout=modal&tmpl=component&cid=" . $this->cid . "&mid=" . $this->mid);
						echo HTMLHelper::_(
							'bootstrap.renderModal',
							'addExistingQuiz' . $this->mid,
							array(
								'url'        => $link,
								'width'      => '800px',
								'height'     => '300px',
								'modalWidth' => '80',
								'bodyHeight' => '70'
							)
						);	
						?>
				</li>

				<!-- DPE Hack start -->
				<li class="list-group-item w-50">
				<span data-js-attr="add-existing-lesson"
					data-bs-toggle="modal"
					data-bs-target="#<?php echo 'addExistingLesson' . $this->mid; ?>"
					class="action btn btn-add-lesson"
					title="<?php echo Text::_('COM_TJLMS_ADD_EXISTING_LESSON'); ?>">

					<span class="icon-new icon-white mr-2" aria-hidden="true"></span>
					<?php echo "Add Existing lesson"; ?>
				</span>

				<?php
					$link  = Route::_("index.php?option=com_tjlms&view=lessonsmodal&layout=default&tmpl=component&cid=" . $this->cid . "&mid=" . $this->mid);
					echo HTMLHelper::_(
						'bootstrap.renderModal',
						'addExistingLesson' . $this->mid,
						[
							'url'    => $link,
							'width'      => '800px',
							'height'     => '300px',
							'modalWidth' => '80',
							'bodyHeight' => '70'
						]
					);
				?>
			</li>
			<!-- DPE Hack End -->
			</ul>

			<?php
			echo HTMLHelper::_(
					'bootstrap.renderModal',
					'lessonTypeModal' . $this->mid,
					array(
						'title'  => Text::_('COM_TJLMS_MODUELS_PICK_LESSONTYPE'),
					),
					$this->loadTemplate('lessontypesmodal')
				); ?>

		</div>
	</li><!--LI for each module ends-->
<?php 
}
?>

