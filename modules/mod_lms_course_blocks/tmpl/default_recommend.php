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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
$tjlmsHelper = new ComtjlmsHelper;
HTMLHelper::stylesheet('media/techjoomla_strapper/bs3/css/bootstrap.css');
HTMLHelper::_('bootstrap.framework');
?>

<?php if ($mod_data->oluser_id): ?>
<div class="recommendUsers panel panel-default br-0">
	<div class="panel-heading">
		<span class="panel-heading__title">
			<i class="fa fa-users"></i>
			<span><?php echo Text::_('MOD_TJLMS_RECOMMEND_PANEL_HEADING')?></span>
		</span>
		<?php
			$recommendLink = $tjlmsHelper->tjlmsRoute( 'index.php?option=com_tjlms&view=enrolluser&tmpl=component&selectedcourse[]=' . $course->id . '&type=reco&course_al=' . $course->access, false);
				?>
		<span class="recommendUsers__action pull-right">
			<a title="<?php echo Text::_('COM_TJLMS_RECOMMEND_LABEL'); ?>" onclick="openAssignRecommendPopups('<?php echo JUri::root();?>', 'recommendmodel', <?php echo $course->id; ?>); jQuery('#recommendmodel' + <?php echo $course->id; ?>).removeClass('hide'); jQuery('#recommendmodel' + <?php echo $course->id; ?>).removeClass('fade');">
			<?php echo Text::_('COM_TJLMS_RECOMMEND_LABEL');?></a>
	<?php
		$link = Uri::root() . 'index.php?option=com_tjlms&view=enrolluser&tmpl=component&selectedcourse[]=' . $course->id .'&type=reco&course_al=' . $course->access;
												
		echo HTMLHelper::_(
					'bootstrap.renderModal',
					'recommendmodel' . $course->id,
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

	<?php	if (!empty($mod_data->getuserRecommendedUsers)):

					$class = 'col-xs-4 pb-10';

					if (isset($mod_data->getuserAssignedUsers) && count($mod_data->getuserAssignedUsers) == 1):
						$class = 'col-xs-12 pb-10';
					endif;

					if (isset($mod_data->getuserAssignedUsers) && count($mod_data->getuserAssignedUsers) == 2):
						$class = 'col-xs-6 pb-10';
					endif;
	?>
			<!--recommend mod_data course to a friend-->
				<?php foreach ($mod_data->getuserRecommendedUsers as $index => $recommeduser):
						$userName = ( $show_user_or_username == 'name' ? $recommeduser->name : $recommeduser->username );
				 ?>
					<div class="<?php echo $class;?> center text-center">

						<?php if (empty($recommeduser->avatar)) : ?>
							<?php	$recommeduser->avatar = Uri::root(true).'/media/com_tjlms/images/default/user.png';	?>
						<?php endif;	?>

						<?php if (!empty($recommeduser->profileurl)) : ?>
							<a class="" target="_blank" href="<?php echo $recommeduser->profileurl?>" >
						<?php endif;	?>
							<div>
								<img class="img-circle solid-border d-inline-block" alt="<?php echo $userName;?>" title="<?php echo $userName;?>" src="<?php echo $recommeduser->avatar;?>" />
							</div>
							<div class="text-truncate" title="<?php echo $userName;?>"><?php echo $userName;?></div>
						<?php if (!empty($recommeduser->profileurl)) : ?>
							</a>
						<?php endif;	?>
					</div>
				<?php endforeach; ?>
		<?php else:	?>
			<?php	echo Text::_('MOD_TJLMS_NO_RECOMMEND'); ?>
		<?php endif; ?>
			</div>
		</div>
	</div>
</div>
<?php endif; ?>

<!-- Add CSS to hide the modal backdrop -->
<style>
.modal-backdrop {
    display: none !important;
}
</style>