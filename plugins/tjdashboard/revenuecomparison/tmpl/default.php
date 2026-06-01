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
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

JHtml::stylesheet(Uri::root().'plugins/tjdashboard/revenuecomparison/assets/css/revenuecomparison.css', array(), true);
JHtml::script('//www.gstatic.com/charts/loader.js');

if ($revenueData['start_year'] == $revenueData['current_year'])
{
	$subtitle = Text::sprintf('PLG_TJDASHBOARD_REVENUE_COMPARISION_GRAPH_SUBTITLE_SINGLE', $revenueData['current_year']);
}
else
{
	$subtitle = Text::sprintf('PLG_TJDASHBOARD_REVENUE_COMPARISION_GRAPH_SUBTITLE', $revenueData['start_year'], $revenueData['current_year']);
}
?>
<div id="tjdashboard_revenue_chart_div"></div>
<script type="text/javascript">
  // [START script_body]
  google.charts.load('current', {'packages':['bar']});
  google.charts.setOnLoadCallback(drawChart);

  function drawChart() {
	 var data = google.visualization.arrayToDataTable(<?php echo json_encode($revenueData['data'])?>);
	var options = {
	  chart: {
		title: "<?php echo Text::_('PLG_TJDASHBOARD_REVENUE_COMPARISION_GRAPH_TITLE')?>",
		subtitle: "<?php echo $subtitle;?>"
	  }
	};

	var chart = new google.charts.Bar(document.getElementById('tjdashboard_revenue_chart_div'));

	chart.draw(data, google.charts.Bar.convertOptions(options));
  }
  // [END script_body]
</script>
