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
<div class="courseTaughtBy panel panel-default br-0">
	<div class="panel-heading">
		<span class="panel-heading__title">
			<i class="fa fa-user"></i>
			<span><?php echo Text::_('COM_TJLMS_TAUGHT_BY')?></span>
		</span>
		<?php if (!empty($mod_data->getCreatedInfo->profileurl)): ?>
			<?php if ($mod_data->tjlmsparams->get('social_integration') == 'easysocial'): ?>
				<span class="courseTaughtBy__action pull-right">
					<?php
						require_once( JPATH_ROOT . '/administrator/components/com_easysocial/includes/foundry.php' );
						// Render css codes
						Foundry::document()->init();
						// This will render the necessary javascripts on the page header.
						Foundry::document()->processScripts();
					?>

						<a href="javascript:void(0);"
						data-es-conversations-compose
						data-es-conversations-id="<?php echo $mod_data->getCreatedInfo->id;?>">
							<?php echo Text::_("MOD_TJLMS_SEND_MESSAGE");?>
						</a>

				</span>
			<?php endif; ?>
		<?php endif ?>
	</div>

	<div class="panel-body center text-center p-15">
			<div class="courseTaughtBy__image">
				<?php if (empty($mod_data->getCreatedInfo->avatar)) : ?>
					<?php	$mod_data->getCreatedInfo->avatar = Uri::root(true).'/media/com_tjlms/images/default/user.png';	?>
				<?php endif;	?>
				<?php if (!empty($mod_data->getCreatedInfo->profileurl)) : ?>
						<a class="" target="_blank" href="<?php echo $mod_data->getCreatedInfo->profileurl?>" style="text-decoration: none !important;" >
				<?php endif; ?>
					<img src="<?php echo $mod_data->getCreatedInfo->avatar; ?>" alt="<?php echo $mod_data->getCreatedInfo->name; ?>" class="img-circle d-inline-block">
					<?php if (!empty($mod_data->getCreatedInfo->profileurl)) : ?>
				</a>
			<?php endif; ?>

			</div>
			<div class="courseTaughtBy__info">
				<span>
					<?php $userName = ($show_user_or_username == 'name' ? $mod_data->getCreatedInfo->name : $mod_data->getCreatedInfo->username); ?>
					<strong><em><?php echo $userName; ?></em></strong>
				</span>
			</div>

	</div>
</div>
