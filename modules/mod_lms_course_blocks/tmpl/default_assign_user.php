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
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;
$tjlmsHelper = new ComtjlmsHelper;
?>

<div class="assignUsers panel panel-default br-0">
	<div class="panel-heading">
		<span class="panel-heading__title">
			<i class="fa fa-users"></i>
			<span><?php echo Text::_('MOD_TJLMS_ASSIGNED_USER')?></span>
		</span>
		
		<span class="assignUsers__action pull-right">
			<a title="<?php echo Text::_('COM_TJLMS_ASSIGN_LABEL'); ?>" onclick="openAssignRecommendPopups('<?php echo JUri::root();?>', 'assignModal', <?php echo $course->id; ?>); jQuery('#assignModal' + <?php echo $course->id; ?>).removeClass('hide'); jQuery('#assignModal' + <?php echo $course->id; ?>).removeClass('fade');">
			<?php echo Text::_('COM_TJLMS_ASSIGN_LABEL');?></a>
						<?php
							$link = Uri::root() . 'index.php?option=com_tjlms&view=enrolluser&tmpl=component&selectedcourse[]=' . $course->id . '&course_al=' . $course->access . '&type=assign';
												
							echo HTMLHelper::_(
								'bootstrap.renderModal',
								'assignModal' . $course->id,
								array(
									'url'        => $link,
									'width'      => '1800px',
									'height'     => '1600px',
									'modalWidth' => '80',
									'bodyHeight' => '70'
								)
							)
							?>
		</span>
	</div>
	<div class="panel-body p-15">
		<div class="container-fluid">
			<div class="row">
		<?php if (!empty($mod_data->getuserAssignedUsers)): ?>
		<?php
				$class = 'col-xs-4 pb-10';

				if (count($mod_data->getuserAssignedUsers) == 1):
					$class = 'col-xs-12 pb-10';
				endif;
				if (count($mod_data->getuserAssignedUsers) == 2):
					$class = 'col-xs-6 pb-10';
				endif;
		?>

		<?php foreach ($mod_data->getuserAssignedUsers as $index => $enroleduser): 
			$userName = ($show_user_or_username == 'name' ? $enroleduser->name : $enroleduser->username);		
		?>
			<div class="<?php echo $class;?> center text-center">
				<?php if (empty($enroleduser->avatar)) : ?>
					<?php	$enroleduser->avatar = Uri::root(true).'/media/com_tjlms/images/default/user.png';	?>
				<?php endif;	?>

				<?php if (!empty($enroleduser->profileurl)) : ?>
					<a class="" target="_blank" href="<?php echo $enroleduser->profileurl?>" >
				<?php endif;	?>
					<div>
						<img class="img-circle solid-border d-inline-block" alt="<?php echo $userName;?>" title="<?php echo $userName;?>" src="<?php echo $enroleduser->avatar;?>" />
					</div>
					<div class="text-truncate" title="<?php echo $userName;?>"><?php echo $userName;?></div>
				<?php if (!empty($enroleduser->profileurl)) : ?>
					</a>
				<?php endif;	?>
			</div>
		<?php endforeach; ?>

		<?php else: ?>
				<div class="text-center center">
					<?php echo Text::_('MOD_TJLMS_NO_ASSIGN_USER');?>
				</div>
		<?php endif; ?>
			</div>
		</div>
	</div>
</div>

