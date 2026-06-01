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

/* Course info */
?>

<div class="courseInfo panel panel-default br-0">
	<div class="panel-heading">
		<span class="panel-heading__title">
			<i class="fa fa-info-circle"></i>
			<span class="course_block_title"><?php echo Text::_('COM_TJLMS_COURSE_INFO')?></span>
		</span>
	</div>
	<div class="panel-body py-15">
		<div>
			<div class="row">
				<div class="col-xs-12 col-sm-6">
					<?php	echo Text::_('TJLMS_COURSE_CREATOR');	?>
				</div>
				<div class="col-xs-12 col-sm-6">
					<?php echo $userName = ($show_user_or_username == 'name' ? $course->creator_name : $course->creator_username); ?>
				</div>
			</div>
			<?php if ($course->type==1): ?>
				<hr class="mt-5 mb-5">
				<div class="row">
					<div class="col-xs-12 col-sm-6">
						<?php	echo Text::_('TJLMS_COURSE_TYPE');	?>
					</div>
					<div class="col-xs-12 col-sm-6">
						<?php echo Text::_('COM_TJLMS_COURSE_PAID'); ?>
					</div>
				</div>
			<?php endif; ?>
			<hr class="mt-5 mb-5">
			<div class="row">
				<div class="col-xs-12 col-sm-6">
					<?php	echo Text::_('TJLMS_COURSE_PUB_DATE');	?>
				</div>
				<div class="col-xs-12 col-sm-6">
					<?php
						echo $course->start_date;
					?>
				</div>
			</div>
			<hr class="mt-5 mb-5">
			<div class="row">
				<div class="col-xs-12 col-sm-6">
					<?php	echo Text::_('TJLMS_COURSE_CRETIFICATE_TERM');	?>
				</div>
				<div class="col-xs-12 col-sm-6">
					<?php
						if($course->certificate_term==0)
						{
							echo Text::_('TJLMS_COURSE_NO_CERTIFICATE');
						}
						else if($course->certificate_term==1)
						{
							echo Text::_('TJLMS_COURSE_COMPLETE_ALL');
						}
						else if($course->certificate_term==2)
						{
							echo Text::_('TJLMS_COURSE_PASS_ALL_LESSONS');
						}
					?>
				</div>
			</div>

		</div>
	</div>
</div>
