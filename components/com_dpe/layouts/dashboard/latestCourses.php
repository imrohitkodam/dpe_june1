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
HTMLHelper::script('media/com_tjlms/js/tjlms.min.js');

$courseModel    = TjLms::model('course', array('ignore_request' => true));
$latestCourses  = $displayData['latestCourses'];
$comTjlmsHelper = new comtjlmsHelper;
?>

<div class="row"> 

<?php

if (empty($latestCourses))
{
	echo Text::_('COM_DPE_ELEARNING_DASHBOARD_NO_ENROLLED_MESSAGE');
}

if (! empty($latestCourses))
{
	foreach ($latestCourses as $latestCourse)
	{
		$courseData     = $courseModel->getData($latestCourse->id);

		foreach($courseData->toc as $courseDetails)
		{
			$lessonId = array();

			foreach($courseDetails->lessons as $lesson)
			{
				$lessonId[] = $lesson->id;
			}
		}

		$lessonUrl  = "index.php?option=com_tjlms&view=lesson&lesson_id=" . $lessonId[0] . "&tmpl=component&cid=" . $latestCourse->id;
		$lessonUrl  = $comTjlmsHelper->tjlmsRoute($lessonUrl, false);
		$onclick    =	"open_lessonforattempt('" . addslashes($lessonUrl) . "','tab','" . $latestCourse->id . "');";

		$courseUrl = "index.php?option=com_tjlms&view=course&id=" . $latestCourse->id;
		$courseUrl = $comTjlmsHelper->tjlmsRoute($courseUrl, false);

		?>
		<div class="tjlmspin col-md-4 mb-4">
			<div class="thumbnail p-0 br-0 tjlmspin__thumbnail border h-100">
				<!--COURSE IMAGE PART-->
				<a href="<?php echo $courseUrl; ?>" class="center" target="_blank">
					<div class="bg-contain bg-repn" title="<?php echo $latestCourse->title;?>" style="background:url('<?php echo $latestCourse->image;?>'); background-position: center center; background-size: cover; background-repeat: no-repeat;">
						<div class="dashboard-overlay">
							<img class="tjlms_pin_image" style="visibility:hidden" src="<?php echo $latestCourse->image;?>">
							<div class="p-2"><strong><?php echo ucfirst($latestCourse->title);?></strong></div>
						</div>
					</div>
				</a>

				<div class="caption tjlmspin__caption p-2">
					<div class="course-short-desc"><small class="tjlmspin__caption_desc"><?php echo $latestCourse->short_desc;?></small></div>
					<div>
						<button class="btn btn-primary btn-small mt-10" onclick="<?php echo $onclick?>" target="_blank">
							<?php echo Text::_('COM_DPE_ELEARNING_DASHBOARD_START_BUTTON'); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
	<?php
	}
}
?>
</div>
<script type="text/javascript">
var root_url = '<?php echo JURI::base(); ?>';
</script>
