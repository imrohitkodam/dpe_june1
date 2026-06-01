<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
?>
<?php if (is_array($this->sourceschart)) { ?>
<?php 
if (!empty($this->sourceschart[0]) && !empty($this->sourceschart[1]) && !empty($this->sourceschart[2])) {
	$dtraffic = $this->sourceschart[0];
	$straffic = $this->sourceschart[1];
	$rtraffoc = $this->sourceschart[2];
	$total = $this->sourceschart[0] + $this->sourceschart[1] + $this->sourceschart[2];
} else { 
	$total = 1; 
	$dtraffic = 1; 	
	$straffic = 1; 
	$rtraffoc = 1; 
}

$direct = number_format((($dtraffic * 100)/$total) , 2);
$reffer = number_format((($rtraffoc * 100)/$total) , 2);
$search = number_format((($straffic * 100)/$total) , 2); ?>
<table>
	<tr>
		<td align="right"><b><?php echo Text::_('COM_RSSEO_GRAPH_DIRECT_TRAFFIC'); ?></b></td>
		<td><?php echo $direct; ?> % <span class="rss_color" style="background:#dc3912"></span></td>
	</tr>
	<tr>
		<td align="right"><b><?php echo Text::_('COM_RSSEO_GRAPH_REFERRING_SITES'); ?></b></td>
		<td><?php echo $reffer; ?> % <span class="rss_color" style="background:#3366cc"></span></td>
	</tr>
	<tr>
		<td align="right"><b><?php echo Text::_('COM_RSSEO_GRAPH_SEARCH_ENGINES'); ?></b></td>
		<td><?php echo $search; ?> % <span class="rss_color" style="background:#ff9900"></span></td>
	</tr>
</table>
<script type="text/javascript">
	var Pie = new google.visualization.DataTable();
	Pie.addColumn('string', '<?php echo Text::_('COM_RSSEO_GRAPH_SOURCE',true); ?>');
	Pie.addColumn('number', '<?php echo Text::_('COM_RSSEO_GA_CHART_VISITS',true); ?>');
	Pie.addRows(3);
	Pie.setValue(0, 0, '<?php echo Text::_('COM_RSSEO_GRAPH_REFERRING_SITES',true); ?>');
	Pie.setValue(0, 1, <?php echo $this->sourceschart[2]; ?>);
	Pie.setValue(1, 0, '<?php echo Text::_('COM_RSSEO_GRAPH_DIRECT_TRAFFIC',true); ?>');
	Pie.setValue(1, 1, <?php echo $this->sourceschart[0]; ?>);
	Pie.setValue(2, 0, '<?php echo Text::_('COM_RSSEO_GRAPH_SEARCH_ENGINES',true); ?>');
	Pie.setValue(2, 1, <?php echo $this->sourceschart[1]; ?>);

	var pieOptions = {
		'legend': 'none',
		'legendFontSize': 12,
		'pieSliceText': 'none',
		'height': '250',
		'width': '100%',
		'backgroundColor': {
			stroke:'#666', 
			fill:'#FFFFFF', 
			strokeSize: 1
		}
	}

	var pie = new google.visualization.PieChart(document.getElementById('rss_pie'));
	
	pie.draw(Pie, pieOptions);

	jQuery(document).resize(function() {
		pie.draw(Pie, pieOptions);
	});
</script>
<?php } else echo $this->sourceschart; ?>