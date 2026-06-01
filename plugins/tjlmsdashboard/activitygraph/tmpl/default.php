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
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

$document = Factory::getDocument();
$document->addScript(Uri::root(true).'/components/com_tjlms/assets/js/raphael.min.js');
$document->addScript(Uri::root(true).'/components/com_tjlms/assets/js/morris.min.js');
$document->addStyleSheet(Uri::root(true).'/components/com_tjlms/assets/css/morris.css');

$class['xsDeviceClass'] = $this->params->get('xsmall_device_col_class', 'col-xs-12');
$class['smDeviceClass'] = $this->params->get('small_device_col_class', 'col-sm-12');
$class['medDeviceClass'] = $this->params->get('medium_device_col_class', 'col-md-12');
$class['largeDeviceClass'] = $this->params->get('large_device_col_class', 'col-lg-12');
?>

<div class="tjlmsdashboard-activitygraph <?php echo implode(" ", $class); ?>">
	<div class="panel panel-default br-0">

		<div class="panel-heading full-width-height d-inline-block">
			<span class="panel-heading__title">
				<span class="fa fa-line-chart" aria-hidden="true"></span>
				<span><?php echo Text::_('PLG_TJLMSDASHBOARD_ACTIVITYGRAPH_MY_ACTIVITY_CHARTS'); ?></span>
			</span>
			<div class="tj-dashboard__chart_legends pull-right">
				<div class="tj-dashboard__activities-legend mr-10 pull-left">
					<span class="tj-dashboard__activities-title d-inline-block mr-5"><?php echo Text::_('PLG_TJLMSDASHBOARD_ACTIVITYGRAPH_ACTIVITIES'); ?></span>
					<span class="tj-dashboard__activities-color d-inline-block valign-middle"></span>
				</div>
				<div class="tj-dashboard__sessions-legend">
					<span class="tj-dashboard__sessions-title d-inline-block mr-5"><?php echo Text::_('PLG_TJLMSDASHBOARD_ACTIVITYGRAPH_SESSIONS'); ?></span>
					<span class="tj-dashboard__sessions-color d-inline-block valign-middle"></span>
				</div>
			</div>
		</div>
		<div class="panel-body p-10">
			<div id="chart_div"></div>
		</div>
	</div>
</div>

<script>
	Morris.Line({
		element: 'chart_div',
		data :<?php echo json_encode($yourActivities);?>,
		xkey: 'time',
		ykeys: ['activity_count','session_count'],
		labels: ['<?php echo Text::_('PLG_TJLMSDASHBOARD_ACTIVITYGRAPH_ACTIVITIES'); ?>','<?php echo Text::_('PLG_TJLMSDASHBOARD_ACTIVITYGRAPH_SESSIONS'); ?>'],
		xLabels: 'day',
		lineColors: ['<?php echo $plg_data->activity_line_color;	?>','<?php echo $plg_data->session_line_color; ?>'],
		hideHover: 'auto',
		resize: true,
	});
</script>

<style>
	.tj-dashboard__activities-color {
		background: <?php echo $plg_data->activity_line_color; ?>;
	}

	.tj-dashboard__sessions-color {
		background: <?php echo $plg_data->session_line_color;	?>;
	}

</style>
