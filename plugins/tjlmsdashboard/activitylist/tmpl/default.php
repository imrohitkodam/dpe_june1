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
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

$class['xsDeviceClass'] = $this->params->get('xsmall_device_col_class', 'col-xs-12');
$class['smDeviceClass'] = $this->params->get('small_device_col_class', 'col-sm-12');
$class['medDeviceClass'] = $this->params->get('medium_device_col_class', 'col-md-6');
$class['largeDeviceClass'] = $this->params->get('large_device_col_class', 'col-lg-6');


$config = Factory::getConfig();
$timezone = $config->get('offset');
$techjoomlacommon = new TechjoomlaCommon;
$lmsparams   = ComponentHelper::getParams('com_tjlms');
$date_format_show = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');
?>

<div class="tjlmsdashboard-activilylist <?php echo implode(" ", $class); ?>">
	<div class="panel panel-default br-0">
		<div class="panel-heading">
			<span class="panel-heading__title">
				<span class="fa fa-clock-o" aria-hidden="true"></span>
				<span><?php echo Text::_('PLG_TJLMSDASHBOARD_ACTIVITY_LIST_ACTIVITYS'); ?></span>
			</span>
				<?php if (!empty($yourActivitiesList) && ($totalActivities > $plg_data->number_of_activities)):	?>

					<a href="<?php echo $this->myActivitiesLink; ?>" rel="{size: {x: 700, y: 500}, handler:'iframe'}"  id="" class="pull-right trigger" >
						<?php echo Text::_('PLG_TJLMSDASHBOARD_ACTIVITY_LIST_VIEW_ALL_LABEL'); ?>
					</a>

				<?php endif;	?>
		</div>
		<div class="panel-body p-10">

			<?php if (empty($yourActivitiesList)): ?>
					<div class="alert alert-warning">
						<?php echo Text::_('PLG_TJLMSDASHBOARD_ACTIVITY_LIST_NO_ACTIVITIES'); ?>
					</div>
			<?php endif; ?>
			<?php foreach ($yourActivitiesList as $ind => $eachActivity)
			{
				if($eachActivity->activityText){
					$localTime = $techjoomlacommon->getDateInLocal($eachActivity->added_time, 0, $date_format_show);
				?>
				<div class="container-fluid">
					<div class="row">
						<?php if ($ind != 0): ?>
							<hr class="hr hr-condensed tjlms_hr_dashboard">
						<?php endif;?>
						<img alt="Your activities" src="<?php echo $dash_icons_path.'activity-icon.png'; ?>" />
						<span><?php echo $eachActivity->activityText; ?>
					- <small title="<?php  echo $localTime;?>"><em><?php echo $comtjlmstrackingHelper->time_elapsed_string($eachActivity->added_time, true); ?></em>
					 </small>
					</div>
				</div>
			<?php
				}
			}
			?>
		</div>
	</div>
</div>
