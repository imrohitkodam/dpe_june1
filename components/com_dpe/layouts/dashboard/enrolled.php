<?php
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\HTML\HTMLHelper;

JLoader::import('components.com_tjlms.models.course', JPATH_SITE);
JLoader::import('components.com_tjlms.helpers.courses', JPATH_SITE);
JLoader::import('components.com_tjlms.helpers.main', JPATH_SITE);

HTMLHelper::StyleSheet('media/techjoomla_strapper/bs3/css/bootstrap.min.css');

$enrolledCourses = $displayData['enrolledCourses'];
$ongoingCourses  = $displayData['ongoingCourses'];
$allCourseLink   = $displayData['allCourseLink'];
$elearingAccess  = $displayData['elearingAccess'];

// Load course model.

$courseModel        = BaseDatabaseModel::getInstance('Course', 'TjlmsModel', array('ignore_request' => true));
$TjlmsCoursesHelper = new TjlmsCoursesHelper;
$comTjlmsHelper     = new comtjlmsHelper;
?>

<?php
if (empty($elearingAccess))
{?>
	<div class="alert alert-warning">
		<?php echo Text::_('COM_DPE_ELEARNING_DASHBOARD_NO_TOOL_ACCESS');?>
	</div>
<?php
}?>

<?php
if (!empty($elearingAccess) && (empty($enrolledCourses) || empty($ongoingCourses)))
{?>
	<div class="alert alert-warning">
		<?php echo Text::sprintf('COM_DPE_ELEARNING_DASHBOARD_NO_ENROLLED_MESSAGE', $allCourseLink);?>
	</div>
<?php
}?>

<?php
if (! empty($enrolledCourses))
{
?>
<div class="row"> 
<?php
	foreach ($enrolledCourses as $enrolledCourse)
	{
		$courseData     = $courseModel->getData($enrolledCourse->id);
		$courseProgress = $TjlmsCoursesHelper->getCourseProgress($enrolledCourse->id, $enrolledCourse->userEnrollment->user_id);

		if ($courseProgress['status'] == "I" || $courseProgress['status'] == "")
		{
			unset($incompleteLessons);
			unset($notStartedLessons);
			unset($lessonData);

			foreach($courseData->toc as $courseDetails)
			{
				foreach($courseDetails->lessons as $lesson)
				{
					if ($lesson->userStatus['status'] === "not_started")
					{
						$notStartedLessons[] = $lesson;
					}
					elseif ($lesson->userStatus['status'] === "incomplete")
					{
						$incompleteLessons[] = $lesson;
					}
					elseif ($lesson->userStatus['status'] === "started")
					{
						$started[] = $lesson;
					}
				}
			}
			
			// If incomplete lessons are available then show incomplete lesson
			if (! empty($incompleteLessons))
			{
				$lessonData = $incompleteLessons[0];
			}
			
			// If incomplete lessons are not available then show not started lesson
			if (! empty($notStartedLessons) && empty($incompleteLessons))
			{
				$lessonData = $notStartedLessons[0];
			}

			// If incomplete lessons are not available then show started lesson
			if (!empty($started) && empty($notStartedLessons) && empty($incompleteLessons))
			{
				$lessonData = $started[0];
			}


			$lessonUrl = "index.php?option=com_tjlms&view=lesson&lesson_id=" . $lessonData->id . "&tmpl=component&cid=" . $enrolledCourse->id;
			$lessonUrl  = $comTjlmsHelper->tjlmsRoute($lessonUrl, false);

			$courseUrl = "index.php?option=com_tjlms&view=course&id=" . $courseData->id;
			$courseUrl = $comTjlmsHelper->tjlmsRoute($courseUrl, false);
	?>

		<div class="tjlmspin col-md-4 mb-4">
			<div class="thumbnail p-0 br-0 tjlmspin__thumbnail border h-100">
				<!--COURSE IMAGE PART-->
				<a href="<?php echo $courseUrl; ?>" class="center" target="_blank">
					<div class="bg-contain bg-repn" title="<?php echo $courseData->title;?>" style="background:url('<?php echo $courseData->image;?>'); background-position: center center; background-size: cover; background-repeat: no-repeat;">
						<div class="dashboard-overlay">
							<img class="tjlms_pin_image" style="visibility:hidden" src="<?php echo $courseData->image;?>">
							<div class="p-2"><strong><?php echo ucfirst($courseData->title);?></strong></div>
						</div>
					</div>
				</a>

				<div class="caption tjlmspin__caption p-2">
					<div class="row mb-2">
						<div class="col-9">
						<div class="progress dashboard-progress">
						  <div class="progress-bar" role="progressbar" aria-valuenow="<?php echo $courseProgress['completionPercent'];?>"
						  aria-valuemin="0" aria-valuemax="100" style="width:<?php echo $courseProgress['completionPercent'];?>%"></div>
						</div>
						</div>
						<div class="col-3"><small>(<?php echo $courseProgress['completionPercent'];?>%)</small></div>
					</div>
					<div class="tjlmspin__caption_desc"><strong><?php echo Text::sprintf('COM_DPE_ELEARNING_DASHBOARD_NEXT_LESSON', $lessonData->title);?></strong></div>
					<div class="course-short-desc"><small class="tjlmspin__caption_desc"><?php echo $courseData->short_desc;?></small></div>
					<div>
						<a class="btn btn-primary btn-small mt-10" href="<?php echo $lessonUrl;?>" target="_blank">
							<?php echo Text::_('COM_DPE_ELEARNING_DASHBOARD_COUNTINUE_BUTTON'); ?>
						</a>
					</div>
				</div>
			</div>
		</div>


	<?php
		
		}
	}
?>
</div>
<?php
}
?>
