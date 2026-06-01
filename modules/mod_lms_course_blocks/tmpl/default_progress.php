<?php
/**
 * @package     LMS_Shika
 * @subpackage  mod_lms_course_progress
 * @copyright   Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://www.techjoomla.com
 */
// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
HTMLHelper::_('bootstrap.renderModal', 'a.tjmodal');
?>

<?php
// If user is not guest then show enrol or Buy now buttons
if ($mod_data->oluser->guest != 1 && !empty($mod_data->checkifuserenroled))
{
	if ($enrolment_pending == 0)
	{
		?>
	<div class="courseProgress panel panel-default br-0">
		<div class="panel-heading">
			<span class="panel-heading__title">
				<span class="fa fa-line-chart" aria-hidden="true"></span>
				<span class="course_block_title"><?php echo Text::_('COM_TJLMS_COURSE_PROGRESS')?></span>
			</span>
		</div>

		<div class="panel-body progressDiv center text-center p-15">
			<div class="progress-pie-chart" data-percent="<?php echo round($mod_data->progress_in_percent); ?>"><!--Pie Chart -->
				<div class="ppc-progress">
					<div class="ppc-progress-fill"></div>
				</div>
				<div class="ppc-percents">
				<div class="pcc-percents-wrapper">
					<span><?php echo round($mod_data->progress_in_percent); ?>%</span>
				</div>
				</div>
			</div><!--End Chart -->
			<!-- If certificate term is pass all lesson then show certificate button -->
			<?php if (($course->certificate_term == 2 || $mod_data->progress_in_percent == 100 || !empty($course->certficateId)) && $course->certificate_term != 0): ?>
					<?php if (!$mod_data->isExpired && isset($mod_data->certificateId)):

						// Get TJcertificate url for display certificate
						$urlOpts          = array ();
						$certificateObj = TJCERT::Certificate($course->certficateId);
						$certificateLink  = $certificateObj->getUrl($urlOpts, false);
					?>
						<a href="<?php echo $certificateLink?>">
							<button class="btn btn-large btn-success tjlms-btn-flat">
								<?php echo Text::_('COM_TJLMS_GET_CERTIFICATE');?>
							</button>
						</a>
						<div class="p-10">
							<?php echo Text::sprintf('MOD_LMS_COURSE_CERTIFICATES_ISSUED_ON',HTMLHelper::_('date', $certificateObj->issued_on, $mod_data->tjlmsparams['date_format_show']));?>
						</div>
					<?php elseif($mod_data->isExpired):
					?>
						<?php if($mod_data->progress_in_percent == 100): ?>
							<div class="cert_expired_title center"><strong><?php echo Text::_('MOD_LMS_COURSE_CERTIFICATES_EXPIRED') ;?></strong></div>
							<div class=" center text-center control-group">
								<button title="<?php echo Text::_('MOD_LMS_COURSE_RETAKE_BUTTON_TOOLTIP');?> " class="btn btn-large btn-block btn-primary tjlms-btn-flat" type="button" id="free_course_button" onclick="tjlms.course.retakeCourse('<?php echo $course->id;?>','<?php echo $mod_data->oluser->id;?>');" ><?php	echo Text::_('MOD_LMS_COURSE_RETAKE_BUTTON')	?>
									<span id="ajax_loader"></span>
								</button>
							</div>
						<?php endif; ?>

					<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
<?php
	}
} ?>

