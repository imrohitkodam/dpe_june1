<?php
/**
 * @package     Shika
 * @subpackage  Module,mod_lms_course_blocks
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
?>

<?php if (!empty ($mod_data->getgroupinfo)):	?>
	<div class="courseGroupInfo panel panel-default br-0"><!--Group info strats-->
		<div class="panel-heading">
			<span class="panel-heading__title">
				<i class="fa fa-group"></i>
				<span><?php echo Text::_('COM_TJLMS_COURSE_GROUP_INFO')?></span>
			</span>
			<span class="courseGroupInfo__action">
				<a href="<?php echo $mod_data->getgroupinfo->userdiscussions_URL; ?>" class="pull-right"><?php echo  Text::_('COM_TJLMS_COURSE_GROUP_DISCUS_FOLLOW') ?> </a>
			</span>
		</div>

		<div class="panel-body p-15">
			<div class="container-fluid">

			<?php if (empty($mod_data->getgroupinfo->userdiscussions)):?>

				<div class="row">
					<?php	echo Text::_('MOD_LMS_NO_DISCUSSION'); ?>
				</div>

			<?php else :
				if (!empty($mod_data->getgroupinfo->userdiscussions)): ?>

					<small>
					<?php echo  Text::_('COM_TJLMS_COURSE_GROUP_INFO_MSG') ?>
					</small>

					<?php foreach ($mod_data->getgroupinfo->userdiscussions as $ind => $gd):
						if (!empty($gd->title)){
					?>
						<div class="row">
							<hr class="hr hr-condensed tjlms_hr_dashboard">
							<a href="<?php echo $gd->discussion_url; ?>"><?php echo $gd->title; ?></a>
						</div>
					<?php }
						endforeach; ?>
				<?php endif; ?>
			<?php endif; ?>
			</div>
		</div>
	</div><!--Group info ends-->
<?php endif; ?>

