<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,tjlmsdashboard,recommendedcourses
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

$class['xsDeviceClass']    = $this->params->get('xsmall_device_col_class', 'col-xs-12');
$class['smDeviceClass']    = $this->params->get('small_device_col_class', 'col-sm-12');
$class['medDeviceClass']   = $this->params->get('medium_device_col_class', 'col-md-6');
$class['largeDeviceClass'] = $this->params->get('large_device_col_class', 'col-lg-6');
?>

<div class="tjdashboard-recommendedcourses <?php echo implode(" ", $class); ?>">
	<div class="panel panel-default  br-0">
		<div class="panel-heading grey_border_class">
			<span class="panel-heading__title">
				<span class="fa fa-graduation-cap" aria-hidden="true"></span>
				<span><?php echo Text::_('PLG_TJLMSDASHBOARD_RECOMMENDED_COURSES_TITLE'); ?></span>
			</span>
		<?php if ($totalCount > $noOfCourses): ?>
				<a href="<?php echo $comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=courses&courses_to_show=recommended', false); ?>" class="pull-right" >
					<?php echo Text::_('PLG_TJLMSDASHBOARD_RECOMMENDED_COURSES_VIEW_ALL_LABEL'); ?>
				</a>
		<?php endif; ?>
		</div>
	<div class="panel-body p-10">
		<?php if (empty($recommcourse['totalCount'])): ?>
				<div class="alert alert-warning">
					<?php echo Text::_('PLG_TJLMSDASHBOARD_RECOMMENDED_COURSES_NO');  ?>
				</div>
		<?php else: ?>
		<?php
			foreach ($recommcourse as $ind => $rc)
			{

				$userData = Factory::getUser($rc->userId);

				if ($userData->block == 1)
				{
					$rc->userWhoRecommendavatar     = "";
					$rc->userWhoRecommendprofileurl = "";
					$userName                       = Text::_('COM_TJLMS_BLOCKED_USER');
				}
				else
				{
					$userName = $showUserOrUsername == 'name' ? $rc->name : $rc->username;
				}

				if($rc->content_title)
				{
			?>
					<div class="container-fluid">
						<div class="row">
							<div class="col-xs-12 col-sm-8">
								<?php $rc->content_url = $comtjlmsHelper->tjlmsRoute($rc->content_url); ?>
								<a href="<?php echo $rc->content_url; ?>"><?php echo $rc->content_title; ?></a>
							</div>

							<div class="col-xs-12 col-sm-4" align="center">
								<small><em><?php echo Text::_('PLG_TJLMSDASHBOARD_RECOMMENDED_COURSES_RECOMMENDEDBY'); ?></em></small>
								<?php if (empty($rc->userWhoRecommendavatar)) : ?>
									<?php	$rc->userWhoRecommendavatar = Uri::root(true).'/media/com_tjlms/images/default/user.png';	?>
								<?php endif;	?>

								<?php if (!empty($rc->userWhoRecommendprofileurl)) : ?>
									<a class="" target="_blank" href="<?php echo $rc->userWhoRecommendprofileurl?>" >
								<?php endif;	?>
									<img class="img-circle solid-border smallcircularimages" title="<?php echo $userName; ?>" src="<?php echo $rc->userWhoRecommendavatar;?>" />
								<?php if (!empty($rc->userWhoRecommendprofileurl)) { ?>
									<div title="<?php echo $userName;?>"><?php echo $userName; ?></div>
									</a>
								<?php }
								else{?>
									<div title="<?php echo $userName;?>"><?php echo $userName; ?></div>
								<?php } ?>
							</div>
						</div>
					</div>
			<?php
				}
			}
		?>
		<?php endif; ?>
	</div>
	</div>
</div>

