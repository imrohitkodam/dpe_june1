<?php
/**
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;

$class['xsDeviceClass'] = $this->params->get('xsmall_device_col_class', 'col-xs-12');
$class['smDeviceClass'] = $this->params->get('small_device_col_class', 'col-sm-12');
$class['medDeviceClass'] = $this->params->get('medium_device_col_class', 'col-md-6');
$class['largeDeviceClass'] = $this->params->get('large_device_col_class', 'col-lg-6');
?>

<div class="your_liked_lessons <?php echo implode(" ", $class); ?>">
	<div class="panel panel-default  br-0">

		<div class="panel-heading">
			<span class="panel-heading__title">
				<span class="fa fa-thumbs-o-up" aria-hidden="true"></span>
				<span><?php echo Text::_('PLG_TJLMSDASHBOARD_LIKED_LESSON_MY_LIKED_LESSONS'); ?></span>
			</span>
		</div>

		<div class="panel-body p-10">

		<?php if (empty($yourLikedLessons)): ?>

				<div class="alert alert-warning">
					<?php echo Text::_('PLG_TJLMSDASHBOARD_LIKED_LESSON_NO_LIKED_LESSONS'); ?>
				</div>

		<?php endif; ?>

		<?php foreach ($yourLikedLessons as $ind => $eachlikedlesson)
		{
		?>
			<div class="liked_lesson">
				<div class="container-fluid">
					<div class="row">

						<?php if ($ind != 0): ?>
								<hr>
						<?php endif; ?>

						<div class="col-xs-8">
							<span><?php echo $eachlikedlesson->title; ?></span>
						</div>
						<?php
						$hovertitle = " title='" . Text::_('COM_TJLMS_LAUNCH_LESSON_TOOLTIP') . "'";
						$active_btn_class = 'btn-small btn-primary';

						$lesson_url = $comtjlmsHelper->tjlmsRoute("index.php?option=com_tjlms&view=lesson&lesson_id=" . $eachlikedlesson->id . "&tmpl=component", false);

						$onclick=	"open_lessonforattempt('" . addslashes($lesson_url) . "','" . $launch_lesson_full_screen ."');";
						?>

						<div class="col-xs-4">
							<button <?php echo $hovertitle; ?> class="btn <?php echo $active_btn_class; ?> pull-right" onclick="<?php echo $onclick?>">
								<span class="lesson_attempt_action hidden-xs hidden-sm"><?php echo Text::_("COM_TJLMS_LAUNCH"); ?>
								</span>
								<span class="glyphicon glyphicon-play hidden visible-sm visible-xs" aria-hidden="true"></span>
							</button>
						</div>
					</div>
				</div>
			</div>
<?php } ?>
		</div>
	</div>
</div>
