<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('techjoomla.common');

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

$class['xsDeviceClass'] = $this->params->get('xsmall_device_col_class', 'col-xs-12');
$class['smDeviceClass'] = $this->params->get('small_device_col_class', 'col-sm-12');
$class['medDeviceClass'] = $this->params->get('medium_device_col_class', 'col-md-12');
$class['largeDeviceClass'] = $this->params->get('large_device_col_class', 'col-lg-12');
?>

<div class="tjdashboard-enrolledcourses <?php echo implode(' ', $class); ?>" >
	<div class="panel panel-default br-0">
		<div class="panel-heading">
			<span class="panel-heading__title">
				<span class="fa fa-graduation-cap" aria-hidden="true"></span>
				<?php echo Text::_('PLG_TJLMSDASHBOARD_ENROLLED_COURSES_TITLE'); ?>
			</span>
			<?php if ($totalRows > $no_of_courses):

				$techjoomlacommon = new TechjoomlaCommon;
				$menuItemID       = $techjoomlacommon->getItemId('index.php?option=com_tjlms&view=courses');

				$urlcourses = $comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=courses&course_status=enrolledcourses&Itemid=' . $menuItemID, false);
				?>
			<a href="<?php echo $urlcourses ?>" id="testing" class="pull-right" >
				<?php echo Text::_('PLG_TJLMSDASHBOARD_ENROLLED_COURSES_VIEW_ALL_LABEL'); ?>
			</a>
			<?php endif; ?>
		</div>
		<div class="panel-body p-10 pb-20">
			<?php if (empty($userCourseinfo)): ?>
				<div class="alert alert-warning">
					<?php echo Text::_('PLG_TJLMSDASHBOARD_ENROLLED_COURSES_NO_ENROLLED_COURSES'); ?>
				</div>

			<?php else: ?>

			<?php foreach ($userCourseinfo as $ind => $course)
					{
					?>

					<?php if ($ind !== 0): ?>
						<hr>
					<?php endif;?>
					<div class="enrolled-course">
						<div class="container-fluid">
							<div class="row">
								<a href="<?php echo $comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $course->id , false); ?>"><?php echo htmlspecialchars($course->title); ?></a>
							</div>
							<div class="row small text-muted pt-10">
								<span class="col-md-3 col-sm-6 col-xs-12 pb-5">
								<?php	if(!empty($course->assign_start_date) && $course->assign_start_date != '0000-00-00 00:00:00'):	?>
									<b><?php echo Text::_('PLG_TJLMSDASHBOARD_ENROLLED_COURSES_ASSIGN_START_DATE');?></b>
									<span>&nbsp;<?php echo $course->assign_start_date; ?></span>
								<?php endif;?>
								</span>
								<span class="col-md-3 col-sm-6 col-xs-12 pb-5">
								<?php	if(!empty($course->assign_due_date) && $course->assign_due_date != '0000-00-00 00:00:00'):	?>
									<b><?php echo Text::_('PLG_TJLMSDASHBOARD_ENROLLED_COURSES_ASSIGN_DUE_DATE');?></b>
									<span>&nbsp;<?php echo $course->assign_due_date; ?></span>
								<?php endif;?>

								</span>
								<span class="col-md-3 col-sm-6 col-xs-12 pb-5">
								<?php if(!empty($course->assigned_by)) : ?>
									<b><?php echo Text::_('PLG_TJLMSDASHBOARD_ENROLLED_COURSES_ASSIGN_ASSIGNED_BY'); ?></b>
									<span>&nbsp;<?php echo (!empty($course->assigned_by)) ? $course->assigned_by : ''?></span>
								<?php endif;?>
								</span>
								<span class="col-md-3 col-sm-6 col-xs-12 pb-5">
							<?php if ($course->module_data['completionPercent'] == 100 && $course->certificate_term != 0): ?>
							<?php if (!$course->cert_expired && $course->certificateId)
								{
									$urlOpts          = array ();
									$certificateLink  = TJCERT::Certificate($course->certificateId)->getUrl($urlOpts, false);
								?>

								<a href="<?php echo $certificateLink?>"><span class="fa fa-certificate" aria-hidden="true"></span> <i><?php echo Text::_('PLG_TJLMSDASHBOARD_GET_CERTIFICATE_LINK')?></i></a>
							<?php } elseif($course->cert_expired)
								{	?>
								<span class="text-danger">
									<?php echo Text::_('COM_TJLMS_COURSE_CERTIFICATES_EXPIRED');?>
								</span>
							<?php	}	?>
						<?php endif; ?>
								</span>
							</div>
						<?php
						$bar_color = 'progress-bar-success';

						if ($course->module_data['completionPercent'] < 50)
						{
							$bar_color = 'progress-bar-danger';
						}

						?>
						<div class="row">
							<div class="col-sm-11 col-xs-10">
								<div class="progress progress-h10 mb-0 mt-10">
									<div class="progress-bar inline <?php echo $bar_color; ?>" role="progressbar" aria-valuenow="<?php echo $course->module_data['completionPercent']; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $course->module_data['completionPercent']; ?>%;">
									</div><!--progress-bar inline-->
								</div><!--progress-->
							</div><!--col-sm-10 col-xs-10-->
							<div class="percent small col-xs-offset-10">
								<?php echo round($course->module_data['completionPercent']). '%';	?>
							</div><!--col-sm-1 col-xs-1-->
						</div><!--row-->

						</div>
					</div>
			<?php	}	?>
		<?php endif; ?>
		</div>
	</div>
</div><!--course progress bar ends-->
