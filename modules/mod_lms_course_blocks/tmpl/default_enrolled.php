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

?>

<div class="enrolledUsers panel panel-default br-0">
	<div class="panel-heading">
		<span class="panel-heading__title">
			<i class="fa fa-users"></i>
			<span class="course_block_title"><?php echo Text::_('COM_TJLMS_ENROLLED_USERS')?></span>
		</span>
		<span class="pull-right">
			<?php if (count($mod_data->getallenroledUsersinfo) > 6): ?>
				<span class="badge"><?php echo count($mod_data->getallenroledUsersinfo);?></span>
				<a href="#" data-bs-toggle="modal" data-bs-target="#mod_lms_enrolledusers"><?php echo Text::_('COM_TJLMS_VIEW_ALL_LABEL')?></a>
			<?php endif ?>
		</span>
	</div>
	<div class="panel-body p-15">
		<div class="container-fluid">
			<div class="row">

				<?php if(!empty($mod_data->getallenroledUsersinfo)):
						$userscount = count($mod_data->getallenroledUsersinfo);

						$class = 'col-xs-4 pb-10';

						if (isset($mod_data->getuserAssignedUsers))
						{
							if (count($mod_data->getuserAssignedUsers) == 1):
								$class = 'col-xs-12 pb-10';
							endif;

							if (count($mod_data->getuserAssignedUsers) == 2):
								$class = 'col-xs-6 pb-10';
							endif;
						}
					?>

					<?php
					$counter = 0;
					foreach($mod_data->getallenroledUsersinfo as $index => $enroleduser): 
						$userName = ($show_user_or_username == 'name' ? $enroleduser->name : $enroleduser->username );
					?>

						<div class="<?php echo $class;?> text-center center">
							<?php
							if (empty($enroleduser->avatar)) : ?>
								<?php	$enroleduser->avatar = Uri::root(true).'/media/com_tjlms/images/default/user.png';	?>
							<?php endif; ?>

							<?php if (!empty($enroleduser->profileurl)) : ?>
								<a class="" target="_blank" href="<?php echo $enroleduser->profileurl?>" >
							<?php endif;	?>
									<div>
										<img class="img-circle solid-border d-inline-block"  alt="<?php echo $userName;?>" title="<?php echo $userName = ($show_user_or_username == 'name' ? $enroleduser->name : $enroleduser->username );?>" src="<?php echo $enroleduser->avatar;?>" />
									</div>
									<div class="text-truncate" title="<?php echo $userName;?>"><?php echo $userName;?></div>
							<?php if (!empty($enroleduser->profileurl)) : ?>
								</a>
							<?php endif;	?>

						</div>
						<?php
						$counter ++;
						if ($counter > 8): ?>

						<?php break; endif ?>
					<?php  endforeach; ?>
					    <!-- Modal content-->
 			 <!-- Modal -->
			  <div class="modal fade" id="mod_lms_enrolledusers" role="dialog">
			    <div class="modal-dialog modal-lg">
			      <div class="modal-content">
			        <div class="modal-header">
			          <button type="button" class="close" data-dismiss="modal">&times;</button>
			          <h4 class="modal-title"><?php echo Text::_('COM_TJLMS_ENROLLED_USERS')?></h4>
			        </div>
			        <div class="modal-body">
				        <div class="row">
				        <?php if ($mod_data->getallenroledUsersinfo): ?>
							<?php foreach($mod_data->getallenroledUsersinfo as $index => $enroleduser): 
									$userName = ($show_user_or_username == 'name' ? $enroleduser->name : $enroleduser->username);
							?>
								<div class="col-xs-4 col-md-3 center text-center pb-10">

									<?php if (empty($enroleduser->avatar)) : ?>
										<?php	$enroleduser->avatar = Uri::root(true).'/media/com_tjlms/images/default/user.png';	?>
									<?php endif; ?>

									<?php if (!empty($enroleduser->profileurl)) : ?>
										<a class="" target="_blank" href="<?php echo $enroleduser->profileurl?>" >
									<?php endif;?>
										<div>
											<img class="img-circle solid-border d-inline-block" alt="<?php echo $userName;?>" title="<?php echo $userName;?>" src="<?php echo $enroleduser->avatar;?>" />
										</div>
										<div class="text-truncate" title="<?php echo $userName;?>"><?php echo $userName;?></div>
									<?php if (!empty($enroleduser->profileurl)) : ?>
										</a>
									<?php endif;	?>
								</div>
							<?php  endforeach; ?>
						<?php endif ?>
						</div>
			        </div>
			      </div>
			    </div>
			  </div>
		<?php else:	?>
			<?php	echo Text::_('MOD_TJLMS_NO_RECOMMEND'); ?>
		<?php  endif; ?>
		</div>
		</div>
	</div>
</div>
<style type="text/css">
#mod_lms_enrolledusers .modal-body{
	height: 600px;
	overflow: auto;
}
</style>
