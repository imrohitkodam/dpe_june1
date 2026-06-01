<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;

$coursePrerequiSite = $this->item->params->get('courseprerequisite');
?>
<?php if (!empty($coursePrerequiSite['onBeforeEnrolCoursePrerequisite']['0']) && PluginHelper::isEnabled('tjlms', 'courseprerequisite')) {?>
<div class="panel-group" id="accordion">
	<?php $courseModel         = TjLms::model('course');
	foreach ($coursePrerequiSite['onBeforeEnrolCoursePrerequisite'] as $courseId) { ?>
	<?php
		$coursedata         = $courseModel->getData($courseId);
		$courseProgress     = $this->tjlmsCoursesHelper->getCourseProgress($courseId, $this->oluser_id);
		$status             = $courseProgress['status'] == 'C' ? 'Completed' : 'Not Completed';
		$statusClass        = $courseProgress['status'] == 'C' ? 'completed' : 'incomplete' ;
	?>
	<div class="row pt-10">
		<div class="col-xs-9">
			<span class="pl-10">
				<span class="d-inline fs-15"><?php echo ucfirst($coursedata->title);?></span>
			</span>
		</div>
		<div class="col-xs-3">
		<?php
			if ($coursedata->allowEnroll)
			{
				$courseData = array();
				$courseData['id'] = $coursedata->id;
				$courseData['title'] = $coursedata->title;
				$courseData['checkPrerequisiteCourseStatus'] = true;
				echo LayoutHelper::render('course.enroll', $courseData);
			}elseif (($coursedata->userEnrollment->id) && !$coursedata->userEnrollment->expired)
			{
				?><a class="btn btn-primary d-block" href="<?php echo $this->tjlmshelperObj->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $courseId, false);; ?>"><?php echo Text::_('COM_TJLMS_CONTINUE'); ?></a><?php
			}
		?>
		</div>
	</div>
	<div class="row">
		<div class="pl-20">
			<span class="label label-success <?php echo $statusClass; ?>">
				<span class="d-inline"><?php echo ucfirst($status);?></span>
			</span>
			<span class="p-10 <?php echo $statusClass; ?>">
				<span class="d-inline"><?php echo $courseProgress['completionPercent'];?>%</span>
			</span>
		</div>
	</div>
	<?php } ?>
</div>
<?php } ?>
