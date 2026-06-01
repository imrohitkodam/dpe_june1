<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Filesystem\File;

use Joomla\CMS\Factory;
Use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('bootstrap.renderModal');

$data         = $displayData;
$app          = Factory::getApplication();
$comparams    = ComponentHelper::getParams('com_tjlms');
$courseName   = $data['title'];
$allowCreator = $comparams->get('allow_creator', 0);
$user         = Factory::getUser();

BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
$tjlmsModelcourse = BaseDatabaseModel::getInstance('course', 'TjlmsModel', array('ignore_request' => true));
$enrollmentData   = $tjlmsModelcourse->enrollmentStatus((object) $data);
$data             = array_merge((array) $data, (array) $enrollmentData);
$currentDateTime  = Factory::getDate()->toSql();

$template    = $app->getTemplate(true)->template;
$override    = JPATH_SITE . '/templates/' . $template . '/html/layouts/com_tjlms/course/';
$enrolBtnText = Tjlms::Utilities()->enrolBtnText($data['start_date']);

$menuitem   = $app->getMenu()->getActive(); // get the active item
$menuParams = $menuitem->getParams();

$module       = ModuleHelper::getModule('mod_lms_course_display');
$moduleParams = new Registry($module->params);
?>

<div class="<?php echo $data['pinclass'];?> tjlmspin">
	<div class="thumbnail p-0 br-0 tjlmspin__thumbnail">
		<!--COURSE IMAGE PART-->
		<?php if ($data['start_date'] <= $currentDateTime) { ?>
			<a href="<?php echo  $data['url']; ?>"  class="center">
		<?php } ?>
			<div class="bg-contain bg-repn bg-lightgrey" title="<?php echo $this->escape($courseName); ?>" style="background-image:url('<?php echo $data['image'];?>'); background-position: center center; height: 200px;">
				<!-- Course category -->
				<?php  if ($menuParams['pin_view_category'] || $moduleParams->get('pin_view_category')) {?>
					<span class="tjlmspin__position tjlmspin__cat"><?php echo $this->escape($data['cat']); ?></span>
				<?php } ?>
				
			</div>
		<?php if ($data['start_date'] <= $currentDateTime) { ?>
			</a>
		<?php } ?>

		<!-- Course tags -->
		<?php if ($menuParams['pin_view_tags'] || $moduleParams->get('pin_view_tags'))
		{
			if (!empty($data['course_tags'])) {

				if (File::exists($override . 'coursepintags.php'))
				{
					echo LayoutHelper::render('com_tjlms.course.coursepintags', $data);
				}
				else
				{
					echo LayoutHelper::render('course.coursepintags', $data, JPATH_SITE . '/components/com_tjlms/layouts');
				}
			}
		}?>

		<div class="caption tjlmspin__caption">
			<h4 class="tjlmspin__caption_title text-truncate">
				<?php if ($data['start_date'] <= $currentDateTime){ ?>
					<a title="<?php echo $this->escape($courseName); ?>" href="<?php echo  $data['url']; ?>">
						<?php echo $this->escape($courseName); ?>
					</a>
				<?php }
				else {?>
					<div title="<?php echo $this->escape($courseName); ?>">
						<?php echo $this->escape($courseName); ?>
					</div>
				<?php } ?>
			</h4>

			<small class="tjlmspin__caption_desc">
			<?php

			$short_desc_char = $comparams->get('pin_short_desc_char', 50);

			if(strlen($data['short_desc']) >= $short_desc_char)
				echo substr($data['short_desc'], 0, $short_desc_char).'...';
			else
				echo $data['short_desc'];
			?>
			</small>

		</div>

		<?php if ($data['type'] != 0 && $data['displayPrice']) : ?>
			<small class="tjlmspin__position tjlmspin__price">
					<?php
						echo $data['displayPrice'];
					?>
			</small>
		<?php endif;?>

		<div class="tjlmspin__likes_users">
			<div class="container-fluid">
				<div class="row text-center mb-10">
					<hr class="mt-10 mb-10">
					<?php if ($data['start_date'] <= $currentDateTime){ ?>
					<div class="col-xs-6 col-sm-6 tjlmspin__likes">
					<?php

						if ($menuParams['pin_view_likes'] || $moduleParams->get('pin_view_likes')) {?>
						<span class=" " title="<?php echo Text::_('COM_TJLMS_LIKES'); ?>">
							<i class="fa fa-thumbs-up"></i>
							<span class="count">
								<b><?php echo $data['likesforCourse']; ?></b>
							</span>
						</span>
					<?php } ?>
					</div>

					<div class="col-xs-6 col-sm-6 tjlmspin__users">
						<?php if ($menuParams['pin_view_enrollments'] || $moduleParams->get('pin_view_enrollments')) {?>
						<span class="  " title="<?php echo Text::_('COM_TJLMS_STUDENT'); ?>">
							<i class="fa fa-user"></i>
							<span class="count">
								<b><?php echo $data['enrolled_users_cnt']; ?></b>
							</span>
						</span>
					<?php } ?>
					</div>
					<?php } ?>
				</div>
			</div>
		</div>
		<?php
				if (($data['allowBuy'] && !$allowCreator) || ($data['allowBuy'] && $allowCreator && $data['created_by'] != $user->id))
				{
					$courseData = array();
					$courseData['id'] = $data['id'];
					$courseData['title'] = $data['title'];
					$courseData['allowBuy'] = $data['allowBuy'];
					$courseData['checkPrerequisiteCourseStatus'] = $data['checkPrerequisiteCourseStatus'];

					if (File::exists($override . 'buy.php'))
					{
						echo LayoutHelper::render('com_tjlms.course.buy', $courseData);
					}
					else
					{
						echo LayoutHelper::render('course.buy', $courseData, JPATH_SITE . '/components/com_tjlms/layouts');
					}
				}
				elseif ($data['allowEnroll'])
				{
					if ($data['start_date'] <= $currentDateTime)
					{
						$courseData = array();
						$courseData['id'] = $data['id'];
						$courseData['title'] = $data['title'];
						$courseData['checkPrerequisiteCourseStatus'] = $data['checkPrerequisiteCourseStatus'];

						if (File::exists($override . 'enroll.php'))
						{
							echo LayoutHelper::render('com_tjlms.course.enroll', $courseData);
						}
						else
						{
							echo LayoutHelper::render('course.enroll', $courseData, JPATH_SITE . '/components/com_tjlms/layouts');
						}
					}
					else
					{?>
						<div class="text-center pb-10" style="color:black">
						<?php echo $enrolBtnText; ?>
						</div><?php
					}
				}
				else
				{
				?>
					<div class="pin__body--btn mb-15">
						<?php
						if (isset($enrollmentData->userEnrollment->state) && !$enrollmentData->userEnrollment->state  &&  $enrollmentData->userEnrollment->id != 0)
						{
							?>
							<button title=""
								class="btn btn-primary btn-block disabled"
								type="button"><?php echo Text::_('COM_TJLMS_COURSE_ENROLLMENT_PENDING_APPROVAL'); ?></button>
							<?php
						}
						else
						{
							if ($data['start_date'] <= $currentDateTime)
							{?>
							<a class="btn btn-primary d-block" href="<?php echo $data['url']; ?>">
									<?php echo Text::_('COM_TJLMS_CONTINUE'); ?>
							</a>
						<?php }
							else
							{?>
								<div class="text-center pb-10" style="color:black">
								<?php echo $enrolBtnText; ?>
								</div><?php
							}
						}?>
					</div>
				<?php
				}
			?>
	</div>
</div>
