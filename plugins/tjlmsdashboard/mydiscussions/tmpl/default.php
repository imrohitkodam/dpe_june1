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

<div class="tjdashboard-mygroups <?php echo implode(" ", $class); ?>">
	<div class="panel panel-default br-0">
		<div class="panel-heading">
			<span class="panel-heading__title">
				<span class="fa fa-graduation-cap" aria-hidden="true"></span>
				<span><?php echo Text::_('PLG_TJLMSDASHBOARD_MY_DISCUSSIONS_TITLE'); ?></span>
			</span>
				<?php
				if($mydiscussions)
				{
					if($mydiscussions['totalCount'])
					{
						if ($mydiscussions['totalCount'] > $no_of_discussions):	?>
							<a href="<?php echo $mydiscussions['viewAll'] ?>" id="view-all-group-link" class="pull-right trigger" >
								<?php echo Text::_('PLG_TJLMSDASHBOARD_MY_DISCUSSIONS_VIEW_ALL_LABEL'); ?>
							</a>
				<?php	endif;
					}
				}	?>
		</div>
		<div class="panel-body p-10">
			<?php

			if(!$mydiscussions || !$mydiscussions['totalCount'])
			{	?>
				<div class="alert alert-warning">
					<?php echo Text::_('PLG_TJLMSDASHBOARD_MY_DISCUSSIONS_NO_GROUPS'); ?>
				</div>
	<?php 	}
			else
			{
				if($mydiscussions['discussion'])
				{
					foreach ($mydiscussions['discussion'] as $ind => $md)
					{

						if($md->title)
						{
							?>
							<div class="container-fluid">
								<div class="row your_each_group">

									<?php if ($ind != 0): ?>
										<hr class="hr hr-condensed tjlms_hr_dashboard">
									<?php endif;?>

									<a href="<?php echo $md->discussion_url; ?>" target="_blank"><?php echo $md->title; ?></a>
								</div>
							</div>
							<?php
						}
					}
				}
			}
		?>
	</div>
	</div>
</div>
