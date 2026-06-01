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
$counts = count($plgresult[1]);

$class['xsDeviceClass'] = $this->params->get('xsmall_device_col_class', 'col-xs-12');
$class['smDeviceClass'] = $this->params->get('small_device_col_class', 'col-sm-12');
$class['medDeviceClass'] = $this->params->get('medium_device_col_class', 'col-md-6');
$class['largeDeviceClass'] = $this->params->get('large_device_col_class', 'col-lg-6');
?>
<div class="tjdashboard-likedcourses <?php echo implode(" ", $class); ?>">
	<div class="panel panel-default  br-0">
		<div class="panel-heading">
		<span class="panel-heading__title">
			<span class="fa fa-thumbs-o-up" aria-hidden="true"></span>
			<span><?php echo Text::_('PLG_TJLMSDASHBOARD_LIKED_COURSE_TITLE'); ?></span>
		</span>
			<?php
				$k=$this->params->get('no_of_courses');
				if ($counts > $k): ?>
				<a href="<?php echo $comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=courses&courses_to_show=liked', false); ?>" class="pull-right" >
					<?php echo Text::_('PLG_TJLMSDASHBOARD_LIKED_COURSE_VIEW_ALL_LABEL'); ?>
				</a>
			<?php endif; ?>
		</div>
		<div class="panel-body p-10">

			<?php if (empty($yourLikedCourses)): ?>

				<div class="alert alert-warning">
					<?php echo Text::_('PLG_TJLMSDASHBOARD_LIKED_COURSE_NO_LIKED_COURSES'); ?>
				</div>

			<?php else: ?>
			<?php foreach ($yourLikedCourses as $ind => $eachlikedcourse)
			{
				?>
				<div class="liked_course">
					<div class="container-fluid">
						<div class="row">

							<?php if ($ind != 0): ?>
									<hr>
							<?php endif; ?>

							<a href="<?php echo $comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $eachlikedcourse->id, false); ?>"><?php echo $eachlikedcourse->title; ?></a>


						</div>
					</div>
				</div>
			<?php } ?>
			<?php endif; ?>
		</div>
	</div>
</div><!--your EACH  liked courses ends-->
